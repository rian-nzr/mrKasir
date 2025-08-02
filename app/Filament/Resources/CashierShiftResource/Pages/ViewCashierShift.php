<?php

namespace App\Filament\Resources\CashierShiftResource\Pages;

use App\Filament\Resources\CashierShiftResource;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewCashierShift extends ViewRecord
{
    protected static string $resource = CashierShiftResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Ensure we always have fresh data with relationships
        $this->record = $this->record->fresh(['user', 'store', 'orders', 'cashOuts']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->isOpen() && auth()->user()->can('edit_cashier_shift')),

            Actions\Action::make('close_shift')
                ->label('Tutup Shift')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn () => $this->record->isOpen())
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->label('')
                        ->content(function () {
                            $expectedCash = $this->record->opening_cash + $this->record->getCashSalesTotal() - $this->record->cash_out_amount;
                            return "Kas yang diharapkan: Rp " . number_format($expectedCash, 0, ',', '.');
                        }),
                    Forms\Components\TextInput::make('closing_cash')
                        ->label('Jumlah Kas Akhir (Hasil Perhitungan)')
                        ->helperText('Hitung dan masukkan jumlah uang tunai yang tersedia saat tutup kasir')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->step(1000),
                    Forms\Components\Textarea::make('closing_notes')
                        ->label('Catatan Penutupan')
                        ->helperText('Catatan tambahan mengenai penutupan shift')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        $shift = $service->closeShift(
                            $this->record->id,
                            $data['closing_cash'],
                            $data['closing_notes'] ?? null
                        );
                        
                        $validation = $service->validateCashDifference($shift);
                        
                        Notification::make()
                            ->title('Shift Berhasil Ditutup')
                            ->body($validation['message'])
                            ->color(match($validation['status']) {
                                'ok' => 'success',
                                'info' => 'info',
                                'warning' => 'warning',
                                default => 'success'
                            })
                            ->send();
                            
                        $this->refreshFormData([
                            'closing_cash',
                            'expected_cash',
                            'cash_difference',
                            'status',
                            'closed_at'
                        ]);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menutup Shift')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('lg')
                ->requiresConfirmation(),

            Actions\Action::make('add_cash_out')
                ->label('Pengeluaran Kas')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->visible(fn () => $this->record->isOpen())
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->step(1000)
                        ->minValue(1),
                    Forms\Components\TextInput::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3),
                    Forms\Components\Toggle::make('require_approval')
                        ->label('Memerlukan Persetujuan')
                        ->default(true)
                        ->helperText('Jika diaktifkan, pengeluaran harus disetujui oleh supervisor'),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        $cashOut = $service->addCashOut(
                            $this->record->id,
                            auth()->id(),
                            $data['amount'],
                            $data['reason'],
                            $data['notes'] ?? null,
                            $data['require_approval'] ?? true
                        );
                        
                        $message = $data['require_approval'] 
                            ? 'Pengeluaran kas berhasil dicatat dan menunggu persetujuan'
                            : 'Pengeluaran kas berhasil dicatat';
                            
                        Notification::make()
                            ->title('Pengeluaran Kas Dicatat')
                            ->body($message)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Mencatat Pengeluaran')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('suspend_shift')
                ->label('Tangguhkan')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->record->isOpen())
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Alasan Penangguhan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        $service->suspendShift($this->record->id, $data['notes']);
                        
                        Notification::make()
                            ->title('Shift Ditangguhkan')
                            ->body('Shift berhasil ditangguhkan')
                            ->warning()
                            ->send();
                            
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menangguhkan Shift')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('resume_shift')
                ->label('Lanjutkan Shift')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->status === 'suspended')
                ->action(function () {
                    try {
                        $service = app(CashierShiftService::class);
                        $service->resumeShift($this->record->id);
                        
                        Notification::make()
                            ->title('Shift Dilanjutkan')
                            ->body('Shift berhasil dilanjutkan')
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Melanjutkan Shift')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Actions\Action::make('download_report')
                ->label('Unduh Laporan')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->visible(fn () => $this->record->isClosed())
                ->action(function () {
                    try {
                        $service = app(CashierShiftService::class);
                        return $service->generateShiftPdf($this->record->id);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Membuat Laporan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Shift')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('shift_number')
                                    ->label('Nomor Shift')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-calculator'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Kasir')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open' => 'success',
                                        'closed' => 'gray',
                                        'suspended' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'open' => 'Terbuka',
                                        'closed' => 'Ditutup',
                                        'suspended' => 'Ditangguhkan',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('location')
                                    ->label('Lokasi')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('Tidak ditentukan'),
                                Infolists\Components\TextEntry::make('opened_at')
                                    ->label('Dibuka')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-clock'),
                                Infolists\Components\TextEntry::make('closed_at')
                                    ->label('Ditutup')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('Belum ditutup')
                                    ->icon('heroicon-o-lock-closed'),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Ringkasan Kas')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('opening_cash')
                                    ->label('Kas Awal')
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                    ->icon('heroicon-o-banknotes')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('closing_cash')
                                    ->label('Kas Akhir')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum ditutup')
                                    ->icon('heroicon-o-banknotes')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('expected_cash')
                                    ->label('Kas yang Diharapkan')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-calculator')
                                    ->color('warning'),
                                Infolists\Components\TextEntry::make('cash_difference')
                                    ->label('Selisih Kas')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-scale')
                                    ->color(fn ($state) => 
                                        $state === null ? 'gray' : 
                                        ($state > 0 ? 'success' : 
                                        ($state < 0 ? 'danger' : 'gray'))
                                    ),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Ringkasan Penjualan')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('calculated_total_sales')
                                    ->label('Total Penjualan')
                                    ->state(fn ($record) => $record->getTotalSales())
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                                    ->icon('heroicon-o-currency-dollar')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('calculated_total_transactions')
                                    ->label('Total Transaksi')
                                    ->state(fn ($record) => $record->getTotalTransactions())
                                    ->formatStateUsing(fn ($state) => ($state ?: 0) . ' transaksi')
                                    ->icon('heroicon-o-shopping-cart')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('calculated_total_discounts')
                                    ->label('Total Diskon')
                                    ->state(fn ($record) => $record->getTotalDiscounts())
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                                    ->icon('heroicon-o-tag')
                                    ->color('warning'),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan Pembukaan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('closing_notes')
                            ->label('Catatan Penutupan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->notes || $record->closing_notes)
                    ->collapsible(),
            ]);
    }
}

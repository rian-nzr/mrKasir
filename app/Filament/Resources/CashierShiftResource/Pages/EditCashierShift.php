<?php

namespace App\Filament\Resources\CashierShiftResource\Pages;

use App\Filament\Resources\CashierShiftResource;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Notifications\Notification;

class EditCashierShift extends EditRecord
{
    protected static string $resource = CashierShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isOpen() && auth()->user()->can('delete_cashier_shift'))
                ->before(function () {
                    // Check if shift has transactions
                    if ($this->record->transactions()->count() > 0) {
                        Notification::make()
                            ->title('Tidak Dapat Menghapus Shift')
                            ->body('Shift yang memiliki transaksi tidak dapat dihapus.')
                            ->danger()
                            ->send();
                        
                        return false;
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert decimal values for display
        $data['opening_cash'] = (float) $data['opening_cash'];
        $data['closing_cash'] = $data['closing_cash'] ? (float) $data['closing_cash'] : null;
        $data['expected_cash'] = $data['expected_cash'] ? (float) $data['expected_cash'] : null;
        $data['cash_difference'] = $data['cash_difference'] ? (float) $data['cash_difference'] : null;
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure only allowed fields can be edited based on shift status
        if ($this->record->isClosed()) {
            // For closed shifts, only allow editing notes and location
            $data = array_intersect_key($data, array_flip([
                'notes',
                'location',
                'closing_notes'
            ]));
        } elseif ($this->record->status === 'suspended') {
            // For suspended shifts, allow editing notes and location
            $data = array_intersect_key($data, array_flip([
                'notes',
                'location',
                'opening_cash' // Allow adjusting opening cash for suspended shifts
            ]));
        } else {
            // For open shifts, don't allow editing critical financial data
            unset($data['closing_cash'], $data['expected_cash'], $data['cash_difference']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Shift Berhasil Diperbarui')
            ->body('Data shift kasir telah berhasil diperbarui.')
            ->success()
            ->send();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('shift_number')
                            ->label('Nomor Shift')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_id')
                            ->label('Kasir')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255)
                            ->helperText('Lokasi fisik atau terminal kasir'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Terbuka',
                                'closed' => 'Ditutup',
                                'suspended' => 'Ditangguhkan',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Informasi Kas')
                    ->schema([
                        Forms\Components\TextInput::make('opening_cash')
                            ->label('Kas Awal')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000)
                            ->disabled(fn () => $this->record->isClosed())
                            ->helperText(fn () => $this->record->isClosed() 
                                ? 'Kas awal tidak dapat diubah untuk shift yang sudah ditutup'
                                : 'Jumlah kas di awal shift'
                            ),

                        Forms\Components\TextInput::make('closing_cash')
                            ->label('Kas Akhir')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Kas akhir hanya dapat diatur saat menutup shift'),

                        Forms\Components\TextInput::make('expected_cash')
                            ->label('Kas yang Diharapkan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dihitung otomatis: kas awal + penjualan tunai - pengeluaran kas'),

                        Forms\Components\TextInput::make('cash_difference')
                            ->label('Selisih Kas')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dihitung otomatis: kas akhir - kas yang diharapkan'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Waktu')
                    ->schema([
                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Waktu Dibuka')
                            ->disabled()
                            ->dehydrated(false)
                            ->format('d/m/Y H:i:s'),

                        Forms\Components\DateTimePicker::make('closed_at')
                            ->label('Waktu Ditutup')
                            ->disabled()
                            ->dehydrated(false)
                            ->format('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Pembukaan')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('closing_notes')
                            ->label('Catatan Penutupan')
                            ->rows(3)
                            ->disabled(fn () => !$this->record->isClosed())
                            ->helperText(fn () => !$this->record->isClosed() 
                                ? 'Catatan penutupan hanya dapat diubah untuk shift yang sudah ditutup'
                                : null
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Statistik Shift')
                    ->schema([
                        Forms\Components\Placeholder::make('total_transactions')
                            ->label('Total Transaksi')
                            ->content(fn () => ($this->record->transactions()->count() ?: 0) . ' transaksi'),

                        Forms\Components\Placeholder::make('total_sales')
                            ->label('Total Penjualan')
                            ->content(fn () => 'Rp ' . number_format($this->record->getTotalSales(), 0, ',', '.')),

                        Forms\Components\Placeholder::make('cash_sales')
                            ->label('Penjualan Tunai')
                            ->content(fn () => 'Rp ' . number_format($this->record->getCashSalesTotal(), 0, ',', '.')),

                        Forms\Components\Placeholder::make('non_cash_sales')
                            ->label('Penjualan Non-Tunai')
                            ->content(fn () => 'Rp ' . number_format($this->record->getNonCashSalesTotal(), 0, ',', '.')),

                        Forms\Components\Placeholder::make('total_discounts')
                            ->label('Total Diskon')
                            ->content(fn () => 'Rp ' . number_format($this->record->getTotalDiscounts(), 0, ',', '.')),

                        Forms\Components\Placeholder::make('cash_out_amount')
                            ->label('Pengeluaran Kas')
                            ->content(fn () => 'Rp ' . number_format($this->record->cash_out_amount, 0, ',', '.')),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}

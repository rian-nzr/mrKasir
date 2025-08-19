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

        // Ensure we always have fresh data with relationships.
        // Use withoutGlobalScopes() to avoid StoreScope or other global scopes
        // affecting the data shown in the admin UI (fixes mismatches vs DB).
        $this->record = \App\Models\CashierShift::withoutGlobalScopes()
            ->with(['user', 'store', 'orders', 'cashOuts'])
            ->find($this->record->id);
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
                    Forms\Components\Section::make('Informasi Saldo Saat Ini')
                        ->description('Saldo keseluruhan di sistem saat ini')
                        ->schema([
                            Forms\Components\Placeholder::make('current_total_balance')
                                ->label('')
                                ->content(function () {
                                    $storeId = $this->record->store_id;
                                    $paymentMethods = \App\Models\PaymentMethod::where('store_id', $storeId)
                                        ->orderBy('is_cash', 'desc')
                                        ->get();
                                    
                                    $totalBalance = $paymentMethods->sum('balance');
                                    
                                    $html = '<div class="space-y-2">';
                                    $html .= '<div class="text-lg font-semibold text-primary-600">';
                                    $html .= 'Total Saldo Sistem: Rp ' . number_format($totalBalance, 0, ',', '.');
                                    $html .= '</div>';
                                    
                                    // Show opening vs current comparison if available
                                    if ($this->record->opening_total_balance) {
                                        $difference = $totalBalance - $this->record->opening_total_balance;
                                        $color = $difference >= 0 ? 'text-green-600' : 'text-red-600';
                                        $html .= '<div class="' . $color . '">';
                                        $html .= 'Perubahan dari Awal: ' . ($difference >= 0 ? '+' : '') . 'Rp ' . number_format($difference, 0, ',', '.');
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '<div class="mt-3 space-y-1">';
                                    foreach ($paymentMethods as $method) {
                                        $color = $method->is_cash ? 'text-green-600' : 'text-blue-600';
                                        $icon = $method->is_cash ? '💰' : ($method->is_ewallet ? '📱' : '💳');
                                        $html .= '<div class="flex justify-between items-center ' . $color . ' text-sm">';
                                        $html .= '<span>' . $icon . ' ' . $method->name . '</span>';
                                        $html .= '<span class="font-medium">Rp ' . number_format($method->balance, 0, ',', '.') . '</span>';
                                        $html .= '</div>';
                                    }
                                    $html .= '</div></div>';
                                    
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ])
                        ->collapsible(),
                        
                    Forms\Components\Section::make('Perhitungan Kas')
                        ->description('Informasi perhitungan kas berdasarkan transaksi')
                        ->schema([
                            Forms\Components\Placeholder::make('cash_calculation')
                                ->label('')
                                ->content(function () {
                                    $expectedCash = $this->record->opening_cash + $this->record->getCashSalesTotal() - $this->record->cash_out_amount;
                                    $html = '<div class="space-y-2">';
                                    $html .= '<div class="flex justify-between"><span>Kas Awal:</span><span class="font-medium">Rp ' . number_format($this->record->opening_cash, 0, ',', '.') . '</span></div>';
                                    $html .= '<div class="flex justify-between"><span>Penjualan Tunai:</span><span class="font-medium text-green-600">+Rp ' . number_format($this->record->getCashSalesTotal(), 0, ',', '.') . '</span></div>';
                                    $html .= '<div class="flex justify-between"><span>Pengeluaran Kas:</span><span class="font-medium text-red-600">-Rp ' . number_format($this->record->cash_out_amount, 0, ',', '.') . '</span></div>';
                                    $html .= '<hr class="my-2">';
                                    $html .= '<div class="flex justify-between text-lg font-semibold"><span>Kas yang Diharapkan:</span><span class="text-primary-600">Rp ' . number_format($expectedCash, 0, ',', '.') . '</span></div>';
                                    $html .= '</div>';
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ])
                        ->collapsible(),
                        
                    Forms\Components\Section::make('Input Kas Fisik')
                        ->description('Hitung uang tunai yang ada di laci kasir')
                        ->schema([
                            Forms\Components\TextInput::make('physical_cash_count')
                                ->label('Jumlah Uang Tunai di Laci (Hasil Perhitungan Fisik)')
                                ->helperText('Hitung secara fisik semua uang tunai yang ada di laci kasir')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->step(1000)
                                ->minValue(0),
                            Forms\Components\Textarea::make('cash_counting_notes')
                                ->label('Catatan Perhitungan Kas')
                                ->helperText('Catatan mengenai perhitungan kas fisik (opsional)')
                                ->rows(2),
                        ]),
                        
                    Forms\Components\Section::make('Catatan Penutupan')
                        ->schema([
                            Forms\Components\Textarea::make('closing_notes')
                                ->label('Catatan Penutupan Shift')
                                ->helperText('Catatan tambahan mengenai penutupan shift')
                                ->rows(3),
                        ])
                        ->collapsible(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        
                        // Use enhanced balance tracking method
                        $shift = $service->closeShiftWithBalanceTracking(
                            $this->record->id,
                            $data['physical_cash_count'], // Use physical count as closing cash
                            $data['physical_cash_count'], // Physical cash count
                            [], // Cash denominations (can be enhanced later)
                            $data['closing_notes'] ?? null
                        );
                        
                        // Get balance summary for notification
                        $balanceSummary = $shift->getBalanceSummaryForDisplay();
                        $differences = $shift->calculateBalanceDifferences();
                        
                        // Create detailed notification message
                        $message = "Shift berhasil ditutup.\n";
                        $message .= "Total Saldo: {$balanceSummary['closing_total']}\n";
                        $message .= "Kas Fisik: {$balanceSummary['physical_cash']}\n";
                        
                        if ($differences['cash_counting_difference'] != 0) {
                            $message .= "Selisih Kas: {$balanceSummary['cash_difference']}";
                        }
                        
                        $notificationColor = 'success';
                        if (abs($differences['total_difference']) > 1000) {
                            $notificationColor = 'warning';
                        }
                        
                        Notification::make()
                            ->title('Shift Berhasil Ditutup')
                            ->body($message)
                            ->color($notificationColor)
                            ->send();
                            
                        $this->refreshFormData([
                            'closing_cash',
                            'physical_cash_count',
                            'closing_balance_snapshot',
                            'closing_total_balance',
                            'expected_total_balance',
                            'total_balance_difference',
                            'cash_counting_difference',
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

                Infolists\Components\Section::make('Tracking Saldo Keseluruhan')
                    ->description('Tracking semua metode pembayaran dari awal hingga akhir shift')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('opening_total_balance')
                                    ->label('Total Saldo Awal')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dicatat')
                                    ->icon('heroicon-o-play')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('closing_total_balance')
                                    ->label('Total Saldo Akhir')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dicatat')
                                    ->icon('heroicon-o-stop')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('calculated_cash_flow')
                                    ->label('Cash Flow')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-arrow-trending-up')
                                    ->color('warning'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('expected_total_balance')
                                    ->label('Saldo yang Diharapkan')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-calculator')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('total_balance_difference')
                                    ->label('Selisih Total Saldo')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-scale')
                                    ->color(fn ($state) => 
                                        $state === null ? 'gray' : 
                                        ($state > 0 ? 'success' : 
                                        ($state < 0 ? 'danger' : 'gray'))
                                    ),
                                Infolists\Components\TextEntry::make('physical_cash_count')
                                    ->label('Kas Fisik (Perhitungan)')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-banknotes')
                                    ->color('primary'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('cash_counting_difference')
                                    ->label('Selisih Kas Fisik vs Sistem')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum dihitung')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->color(fn ($state) => 
                                        $state === null ? 'gray' : 
                                        (abs($state) < 1000 ? 'success' : 
                                        (abs($state) < 10000 ? 'warning' : 'danger'))
                                    ),
                                Infolists\Components\TextEntry::make('balance_comparison_summary')
                                    ->label('Status Saldo')
                                    ->state(function ($record) {
                                        if (!$record->closing_total_balance) return 'Shift belum ditutup';
                                        
                                        $totalDiff = $record->total_balance_difference ?? 0;
                                        $cashDiff = $record->cash_counting_difference ?? 0;
                                        
                                        if (abs($totalDiff) < 1000 && abs($cashDiff) < 1000) {
                                            return '✅ Seimbang - Semua saldo sesuai';
                                        } elseif (abs($totalDiff) < 10000 && abs($cashDiff) < 10000) {
                                            return '⚠️ Perlu Perhatian - Ada selisih kecil';
                                        } else {
                                            return '❌ Tidak Seimbang - Perlu investigasi';
                                        }
                                    })
                                    ->icon('heroicon-o-check-circle')
                                    ->color(function ($record) {
                                        if (!$record->closing_total_balance) return 'gray';
                                        
                                        $totalDiff = abs($record->total_balance_difference ?? 0);
                                        $cashDiff = abs($record->cash_counting_difference ?? 0);
                                        
                                        if ($totalDiff < 1000 && $cashDiff < 1000) return 'success';
                                        if ($totalDiff < 10000 && $cashDiff < 10000) return 'warning';
                                        return 'danger';
                                    }),
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

                Infolists\Components\Section::make('Ringkasan Laba & Profit')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('calculated_total_profit')
                                    ->label('Total Profit')
                                    ->state(fn ($record) => $record->getTotalProfit())
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                                    ->icon('heroicon-o-chart-bar')
                                    ->color('success')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('calculated_transaction_profit')
                                    ->label('Profit dari Biaya Admin')
                                    ->state(fn ($record) => $record->getTotalTransactionProfit())
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                                    ->icon('heroicon-o-banknotes')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('calculated_order_profit')
                                    ->label('Profit dari Penjualan')
                                    ->state(fn ($record) => $record->getTotalProfit() - $record->getTotalTransactionProfit())
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                                    ->icon('heroicon-o-shopping-cart')
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

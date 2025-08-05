<?php

namespace App\Filament\Resources\CashierShiftResource\Pages;

use App\Filament\Resources\CashierShiftResource;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ListCashierShifts extends ListRecords
{
    protected static string $resource = CashierShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_shift')
                ->label('Buka Shift Baru')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => !CashierShift::getActiveShiftForUser(auth()->id()))
                ->form([
                    Forms\Components\Section::make('Saldo Sistem Saat Ini')
                        ->description('Informasi saldo yang tercatat di sistem (untuk referensi)')
                        ->schema([
                            Forms\Components\Placeholder::make('current_balance_info')
                                ->label('')
                                ->content(function () {
                                    $storeId = auth()->user()->store_id ?? session('selected_store_id');
                                    if (!$storeId) return 'Store tidak ditemukan';
                                    
                                    $paymentMethods = \App\Models\PaymentMethod::where('store_id', $storeId)
                                        ->orderBy('is_cash', 'desc')
                                        ->get();
                                    
                                    $totalBalance = $paymentMethods->sum('balance');
                                    
                                    $html = '<div class="space-y-2">';
                                    $html .= '<div class="text-sm text-gray-600 mb-2">';
                                    $html .= '⚠️ Catatan: Saldo awal shift akan menggunakan kas awal yang Anda input, bukan saldo sistem ini';
                                    $html .= '</div>';
                                    $html .= '<div class="text-lg font-semibold text-gray-500">';
                                    $html .= 'Total Saldo Sistem: Rp ' . number_format($totalBalance, 0, ',', '.');
                                    $html .= '</div>';
                                    
                                    foreach ($paymentMethods as $method) {
                                        $color = $method->is_cash ? 'text-yellow-600' : 'text-blue-600';
                                        $icon = $method->is_cash ? '💰' : ($method->is_ewallet ? '📱' : '💳');
                                        $note = $method->is_cash ? ' (akan diganti dengan kas awal)' : '';
                                        $html .= '<div class="flex justify-between items-center ' . $color . ' text-sm">';
                                        $html .= '<span>' . $icon . ' ' . $method->name . $note . '</span>';
                                        $html .= '<span class="font-medium">Rp ' . number_format($method->balance, 0, ',', '.') . '</span>';
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                    
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ])
                        ->collapsible(),
                        
                    Forms\Components\Section::make('Buka Shift Kasir')
                        ->description('Total saldo awal = Kas awal (input) + Saldo non-cash sistem')
                        ->schema([
                            Forms\Components\TextInput::make('opening_cash')
                                ->label('Kas Awal (Uang Tunai di Laci)')
                                ->helperText('Hitung dan masukkan jumlah uang tunai fisik yang ada di laci kasir. Ini akan menjadi saldo cash awal untuk shift.')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->step(1000)
                                ->minValue(0),
                            Forms\Components\Placeholder::make('balance_calculation_info')
                                ->label('Perhitungan Total Saldo Awal')
                                ->content(function () {
                                    $storeId = auth()->user()->store_id ?? session('selected_store_id');
                                    if (!$storeId) return 'Store tidak ditemukan';
                                    
                                    $nonCashBalance = \App\Models\PaymentMethod::where('store_id', $storeId)
                                        ->where('is_cash', false)
                                        ->sum('balance');
                                    
                                    $html = '<div class="text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">';
                                    $html .= '<div class="font-medium mb-1">Formula: Total Saldo Awal = Kas Awal (input) + Saldo Non-Cash</div>';
                                    $html .= '<div>Contoh: Rp 50.000 (kas awal) + Rp ' . number_format($nonCashBalance, 0, ',', '.') . ' (non-cash) = Rp ' . number_format(50000 + $nonCashBalance, 0, ',', '.') . '</div>';
                                    $html .= '</div>';
                                    
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                            Forms\Components\TextInput::make('location')
                                ->label('Lokasi/Counter')
                                ->helperText('Pilih laci kasir atau lokasi kerja (opsional)')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan Tambahan')
                                ->helperText('Tambahkan keterangan atau kondisi khusus (opsional)')
                                ->rows(3),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        $shift = $service->openShift(
                            auth()->id(),
                            $data['opening_cash'],
                            $data['location'] ?? null,
                            $data['notes'] ?? null
                        );
                        
                        Notification::make()
                            ->title('Shift Berhasil Dibuka')
                            ->body("Shift {$shift->getFormattedShiftNumber()} telah dibuka dengan kas awal Rp " . number_format($data['opening_cash'], 0, ',', '.'))
                            ->success()
                            ->send();
                            
                        return redirect()->to(CashierShiftResource::getUrl('view', ['record' => $shift]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Membuka Shift')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('lg'),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create_cashier_shift')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\CashierShiftStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            
            'open' => Tab::make('Terbuka')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open'))
                ->badge(fn () => CashierShift::where('status', 'open')->count())
                ->badgeColor('success'),
                
            'closed' => Tab::make('Ditutup')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed'))
                ->badge(fn () => CashierShift::where('status', 'closed')->count())
                ->badgeColor('gray'),
                
            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('opened_at', today()))
                ->badge(fn () => CashierShift::whereDate('opened_at', today())->count())
                ->badgeColor('info'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\CashierShift;
use Filament\Support\Enums\IconColor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CashierShiftStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // For super admin, use selected store from session
        if ($user->isSuperAdmin()) {
            $storeId = session('selected_store_id');
            if (!$storeId) {
                return [
                    Stat::make('Status', 'Pilih Toko Terlebih Dahulu')
                        ->description('Silakan pilih toko untuk melihat statistik kasir')
                        ->descriptionIcon('heroicon-m-exclamation-triangle')
                        ->color('warning'),
                ];
            }
            $store = \App\Models\Store::find($storeId);
        } else {
            $store = $user->store;
            if (!$store) {
                return [
                    Stat::make('Status', 'Tidak Ada Toko')
                        ->description('User tidak memiliki toko yang terkait')
                        ->descriptionIcon('heroicon-m-exclamation-triangle')
                        ->color('danger'),
                ];
            }
        }

        // Get current open shift for this user
        $currentShift = CashierShift::where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // Get today's closed shifts for this store
        $todayClosedShifts = CashierShift::where('store_id', $store->id)
            ->where('status', 'closed')
            ->whereDate('closed_at', today())
            ->get();

        // Get this week's stats for this store
        $weekShifts = CashierShift::where('store_id', $store->id)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        // Calculate stats
        $todaySales = $todayClosedShifts->sum(function ($shift) {
            return $shift->getTotalSales();
        });

        $todayTransactions = $todayClosedShifts->sum(function ($shift) {
            return $shift->transactions()->count();
        });

        $weekSales = $weekShifts->sum(function ($shift) {
            return $shift->getTotalSales();
        });

        $currentShiftSales = $currentShift ? $currentShift->getTotalSales() : 0;
        $currentShiftTransactions = $currentShift ? $currentShift->transactions()->count() : 0;

        $stats = [];

        // Current Shift Status
        if ($currentShift) {
            $stats[] = Stat::make('Shift Aktif', $currentShift->shift_number)
                ->description('Dibuka pada ' . $currentShift->opened_at->format('H:i'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/cashier-shifts/' . $currentShift->id);

            $stats[] = Stat::make('Penjualan Shift Ini', 'Rp ' . number_format($currentShiftSales, 0, ',', '.'))
                ->description($currentShiftTransactions . ' transaksi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info');
        } else {
            $stats[] = Stat::make('Status Shift', 'Tidak Ada Shift Aktif')
                ->description('Klik untuk membuka shift baru')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/cashier-shifts?activeTab=0');
        }

        // Today's Performance
        $stats[] = Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($todaySales, 0, ',', '.'))
            ->description($todayTransactions . ' transaksi dari ' . $todayClosedShifts->count() . ' shift')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color($todaySales > 0 ? 'success' : 'gray');

        // Weekly Performance
        $stats[] = Stat::make('Penjualan Minggu Ini', 'Rp ' . number_format($weekSales, 0, ',', '.'))
            ->description($weekShifts->count() . ' shift selesai')
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color($weekSales > 0 ? 'primary' : 'gray');

        return $stats;
    }

    protected function getColumns(): int
    {
        return 4;
    }

    public function getDisplayName(): string
    {
        return 'Statistik Kasir';
    }

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()->can('view_cashier_shift');
    }
}

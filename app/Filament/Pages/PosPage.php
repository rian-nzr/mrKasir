<?php

namespace App\Filament\Pages;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Models\CashierShift;
use Filament\Notifications\Notification;

class PosPage extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pos-pages';

    protected static ?string $slug = 'pos';

    protected static ?string $title = 'Halaman Kasir';

    public static function shouldRegisterNavigation(): bool
    {
        // Only show navigation if user has active cashier shift
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        
        // Get store_id based on user type
        $storeId = null;
        if ($user->hasRole('super_admin')) {
            // For super admin, check if there's any active shift or use selected store
            $storeId = session('selected_store_id');
            
            // If no specific store selected, check if there's any active shift
            if (!$storeId) {
                return CashierShift::where('status', CashierShift::STATUS_OPEN)->exists();
            }
        } else {
            // For regular users, use their assigned store
            $storeId = $user->store_id;
        }

        if (!$storeId) {
            return false;
        }

        $activeShift = CashierShift::where('store_id', $storeId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->exists();

        return $activeShift;
    }

    public static function getNavigationBadge(): ?string
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();
        
        // Get store_id based on user type
        $storeId = null;
        if ($user->hasRole('super_admin')) {
            // For super admin, check if there's any active shift or use selected store
            $storeId = session('selected_store_id');
            
            // If no specific store selected, check if there's any active shift
            if (!$storeId) {
                $anyActiveShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->exists();
                return $anyActiveShift ? 'AKTIF' : null;
            }
        } else {
            // For regular users, use their assigned store
            $storeId = $user->store_id;
        }

        if (!$storeId) {
            return null;
        }

        $activeShift = CashierShift::where('store_id', $storeId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->first();

        return $activeShift ? 'AKTIF' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public function mount(): void
    {
        $user = auth()->user();
        
        // Get store_id based on user type
        $storeId = null;
        if ($user->hasRole('super_admin')) {
            // For super admin, check if there's any active shift or use selected store
            $storeId = session('selected_store_id');
            
            // If no specific store selected, check if there's any active shift
            if (!$storeId) {
                $anyActiveShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->exists();
                if ($anyActiveShift) {
                    return; // Allow access if any shift is active
                }
            }
        } else {
            // For regular users, use their assigned store
            $storeId = $user->store_id;
        }

        if (!$storeId) {
            Notification::make()
                ->title('Store Belum Dipilih')
                ->body('Silakan pilih store terlebih dahulu atau buka kasir.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect('/admin');
            return;
        }

        // Check if user has an active cashier shift for the specific store
        $activeShift = CashierShift::where('store_id', $storeId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->first();

        if (!$activeShift) {
            Notification::make()
                ->title('Kasir Belum Dibuka')
                ->body('Anda harus membuka kasir terlebih dahulu sebelum dapat mengakses halaman POS.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect('/admin');
        }
    }

    public function getMaxContentWidth(): MaxWidth
{
    return MaxWidth::Full;
}

public function getHeader(): ?View
{
    return view('filament.layouts.layout');
}
   

}

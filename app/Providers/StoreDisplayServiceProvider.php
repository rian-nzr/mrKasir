<?php

namespace App\Providers;

use App\Models\Store;
use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class StoreDisplayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerRenderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => $this->getStoreStatusHtml()
            );
        });
    }

    private function getStoreStatusHtml(): string
    {
        if (!Auth::check()) {
            return '';
        }

        $user = Auth::user();
        $currentStore = null;
        
        if ($user->isSuperAdmin()) {
            $selectedStoreId = Session::get('selected_store_id');
            if ($selectedStoreId) {
                $currentStore = Store::find($selectedStoreId);
            }
        } else {
            $currentStore = $user->store;
        }

        if (!$currentStore) {
            return '<div class="mr-4 px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Tidak ada toko dipilih
                    </div>';
        }

        return '<div class="mr-4 flex items-center space-x-2">
                    <div class="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                        <i class="fas fa-store mr-1"></i>
                        ' . $currentStore->name . ' (' . $currentStore->code . ')
                    </div>
                    ' . ($user->isSuperAdmin() ? 
                        '<a href="' . route('store.select') . '" class="text-xs text-primary-600 hover:text-primary-800 underline">
                            Ganti
                        </a>' : ''
                    ) . '
                </div>';
    }
}

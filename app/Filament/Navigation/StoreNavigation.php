<?php

namespace App\Filament\Navigation;

use App\Models\Store;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class StoreNavigation
{
    public static function getNavigationItems(): array
    {
        if (!Auth::check()) {
            return [];
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
            return [
                NavigationItem::make('Pilih Toko')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->badge('Required')
                    ->badgeColor('danger')
                    ->url(route('store.select'))
                    ->visible(fn() => $user->isSuperAdmin())
                    ->sort(-100),
            ];
        }

        return [
            NavigationItem::make('Toko Aktif: ' . $currentStore->name)
                ->icon('heroicon-o-building-storefront')
                ->badge($currentStore->code)
                ->badgeColor('success')
                ->url($user->isSuperAdmin() ? route('store.select') : '#')
                ->sort(-100),
        ];
    }
}

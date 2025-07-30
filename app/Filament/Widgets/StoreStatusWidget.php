<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class StoreStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.store-status-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -10; // Display at the top
    
    public function getViewData(): array
    {
        $user = Auth::user();
        $currentStore = null;
        $stores = [];
        
        if ($user->isSuperAdmin()) {
            // Super admin - get selected store from session
            $selectedStoreId = Session::get('selected_store_id');
            if ($selectedStoreId) {
                $currentStore = Store::find($selectedStoreId);
            }
            $stores = Store::where('is_active', true)->get();
        } else {
            // Regular user - get assigned store
            $currentStore = $user->store;
        }
        
        return [
            'user' => $user,
            'currentStore' => $currentStore,
            'stores' => $stores,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ];
    }
    
    public function selectStore($storeId)
    {
        if (Auth::user()->isSuperAdmin()) {
            Session::put('selected_store_id', $storeId);
            $this->dispatch('store-changed');
            
            // Redirect to refresh the page
            return redirect()->to(request()->header('Referer') ?: '/');
        }
    }
}

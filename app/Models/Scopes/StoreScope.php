<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;

class StoreScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        
        // If user is not authenticated, don't apply any scope
        if (!$user) {
            return;
        }
        
        // SUPER ADMIN HAS FULL ACCESS - NO STORE FILTERING
        if ($user->hasRole('super_admin')) {
            // Super admin can see all data from all stores
            // Optional: If super admin selects a specific store, filter by that
            if (Session::has('selected_store_id') && !request()->query('show_all_stores')) {
                $builder->where('store_id', Session::get('selected_store_id'));
            }
            // Otherwise, show all data without filtering
            return;
        }
        
        // For non-super admin users
        // If user has a specific store_id (like kasir/admin), use that
        if ($user->store_id) {
            $builder->where('store_id', $user->store_id);
            return;
        }
        
        // If user doesn't have specific store but session has selected store
        if (Session::has('selected_store_id')) {
            $builder->where('store_id', Session::get('selected_store_id'));
            return;
        }
        
        // Default: no filtering (show all)
    }
}

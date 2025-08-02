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
        
        // If user is authenticated
        if ($user) {
            // If user has a specific store_id (like kasir), use that
            if ($user->store_id) {
                $builder->where('store_id', $user->store_id);
                return;
            }
            
            // If user is super admin or admin without specific store, check session
            if (Session::has('selected_store_id')) {
                $builder->where('store_id', Session::get('selected_store_id'));
                return;
            }
            
            // If user is super admin but no store selected, don't apply scope
            if ($user->hasRole('super_admin')) {
                return;
            }
        }
        
        // Fallback: use session if available
        if (Session::has('selected_store_id')) {
            $builder->where('store_id', Session::get('selected_store_id'));
        }
    }
}

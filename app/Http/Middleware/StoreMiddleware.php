<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class StoreMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware untuk authentication routes
        if ($request->routeIs(['logout', 'login*'])) {
            return $next($request);
        }

        // Skip jika user belum login
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Super admin has full access without store restrictions
        if ($user->hasRole('super_admin')) {
            // Super admin can access all stores - no restrictions
            // Optionally set selected store if provided in request
            if ($request->has('store_id')) {
                Session::put('selected_store_id', $request->input('store_id'));
            }
            // If no store selected, super admin sees all data
            return $next($request);
        }
        
        // Cek apakah user punya store_id tetap (seperti kasir/admin)
        if ($user->store_id) {
            // Set store dari user ke session
            Session::put('selected_store_id', $user->store_id);
        } else {
            // For non-super admin users without store_id, might need store selection
            if (!Session::has('selected_store_id')) {
                // Could redirect to store selection or show error
                // For now, let it pass
            }
        }

        return $next($request);
    }
}

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
        
        // Cek apakah user punya store_id tetap (seperti kasir)
        if ($user->store_id) {
            // Set store dari user ke session
            Session::put('selected_store_id', $user->store_id);
        } else {
            // Untuk super admin, periksa apakah sudah ada store yang dipilih
            // Jika belum dan bukan di route store selection, mungkin perlu redirect
            if ($user->hasRole('super_admin') && !Session::has('selected_store_id')) {
                // Biarkan super admin tanpa store selection untuk flexibility
                // Atau bisa redirect ke store selection jika diperlukan
            }
        }

        return $next($request);
    }
}

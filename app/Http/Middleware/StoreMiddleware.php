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
        // Skip middleware untuk route authentication dan logout
        if ($request->routeIs(['filament.admin.auth.*', 'logout', 'store.*'])) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            
            // Jika super admin, biarkan mereka memilih toko
            if ($user->isSuperAdmin()) {
                // Jika belum ada toko yang dipilih di session, redirect ke halaman pilih toko
                if (!Session::has('selected_store_id') && !$request->routeIs('store.select*')) {
                    return redirect()->route('store.select');
                }
            } else {
                // Jika bukan super admin, otomatis set store dari user
                if ($user->store_id) {
                    Session::put('selected_store_id', $user->store_id);
                } else {
                    // Jika user tidak punya store, redirect ke halaman error atau assign store
                    return redirect()->route('store.not-assigned');
                }
            }
        }

        return $next($request);
    }
}

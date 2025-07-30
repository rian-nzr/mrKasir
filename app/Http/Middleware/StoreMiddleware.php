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
        // Skip middleware untuk semua route admin, authentication dan store
        if ($request->is('admin*') || 
            $request->routeIs(['filament.*', 'logout', 'store.*', 'login*'])) {
            return $next($request);
        }

        // Skip jika user belum login
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Cek apakah user punya store_id
        if ($user->store_id) {
            // Set store dari user ke session
            Session::put('selected_store_id', $user->store_id);
        } else {
            // Jika user tidak punya store, biarkan default behavior
            // atau redirect ke halaman assign store jika diperlukan
        }

        return $next($request);
    }
}

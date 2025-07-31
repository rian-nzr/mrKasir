<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EnsureStoreSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Jika user belum login, lanjutkan request (biarkan auth middleware handle)
        if (!$user) {
            return $next($request);
        }
        
        // Jika user bukan super admin, lanjutkan request
        if (!$user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Skip semua pengecekan jika sedang di halaman store-selection atau route khusus
        if (str_contains($request->path(), 'store-selection') || 
            $request->is('store/change') || 
            $request->is('logout') ||
            $request->routeIs('admin.store-selection') ||
            $request->routeIs('admin.store-selection.select')) {
            return $next($request);
        }
        
        // Jika super admin belum memilih toko, redirect ke halaman store selection
        if (!Session::has('selected_store_id')) {
            return redirect('/store/select');
        }
        
        return $next($request);
    }
}

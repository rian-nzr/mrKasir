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
        
        // Jika user bukan super admin, lanjutkan request (kasir/admin dengan store_id tetap)
        if (!$user->isSuperAdmin()) {
            return $next($request);
        }
        
        // SUPER ADMIN WAJIB PILIH TOKO DULU
        // Skip pengecekan hanya untuk route tertentu
        if ($request->is('store/select') || 
            $request->is('store/change') || 
            $request->is('logout') ||
            $request->routeIs('store.select') ||
            $request->is('admin/logout')) {
            return $next($request);
        }
        
        // Jika super admin belum memilih toko, redirect ke halaman store selection
        if (!Session::has('selected_store_id')) {
            // Redirect ke halaman pemilihan toko
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please select a store first'], 422);
            }
            
            return redirect()->route('store.select')
                ->with('message', 'Silakan pilih toko terlebih dahulu');
        }
        
        // Verifikasi bahwa store yang dipilih masih valid
        $selectedStoreId = Session::get('selected_store_id');
        $store = \App\Models\Store::find($selectedStoreId);
        
        if (!$store || !$store->is_active) {
            Session::forget('selected_store_id');
            return redirect()->route('admin.store-selection')
                ->with('error', 'Toko yang dipilih tidak valid. Silakan pilih toko lain.');
        }
        
        return $next($request);
    }
}

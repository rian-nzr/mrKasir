<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CashierShift;
use Filament\Notifications\Notification;

class CheckCashierShift
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for non-authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        // Get store_id based on user type
        $storeId = null;
        if ($user->hasRole('super_admin')) {
            // For super admin, check if there's any active shift or use selected store
            $storeId = session('selected_store_id');
            
            // If no specific store selected, check if there's any active shift
            if (!$storeId) {
                $anyActiveShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->exists();
                if ($anyActiveShift) {
                    return $next($request); // Allow access if any shift is active
                }
            }
        } else {
            // For regular users, use their assigned store
            $storeId = $user->store_id;
        }

        if (!$storeId) {
            // If this is an AJAX request or API call, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Store belum dipilih atau kasir belum dibuka.',
                    'redirect' => '/admin'
                ], 403);
            }

            // For web requests, show notification and redirect
            Notification::make()
                ->title('Store Belum Dipilih')
                ->body('Silakan pilih store terlebih dahulu atau buka kasir.')
                ->warning()
                ->persistent()
                ->send();

            return redirect('/admin');
        }

        // Check if user has an active cashier shift for the specific store
        $activeShift = CashierShift::where('store_id', $storeId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->first();

        if (!$activeShift) {
            // If this is an AJAX request or API call, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Kasir belum dibuka. Silakan buka kasir terlebih dahulu.',
                    'redirect' => '/admin'
                ], 403);
            }

            // For web requests, show notification and redirect
            Notification::make()
                ->title('Kasir Belum Dibuka')
                ->body('Anda harus membuka kasir terlebih dahulu sebelum dapat mengakses halaman POS.')
                ->danger()
                ->persistent()
                ->send();

            return redirect('/admin');
        }

        return $next($request);
    }
}

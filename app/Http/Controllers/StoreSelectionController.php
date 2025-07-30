<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class StoreSelectionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Hanya super admin yang bisa akses halaman ini
        if (!$user->isSuperAdmin()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }
        
        $stores = Store::where('is_active', true)->get();
        
        return view('store-selection', compact('stores'));
    }
    
    public function select(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id'
        ]);
        
        $user = Auth::user();
        
        // Hanya super admin yang bisa memilih toko
        if (!$user->isSuperAdmin()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }
        
        // Simpan pilihan toko ke session
        Session::put('selected_store_id', $request->store_id);
        
        return redirect()->route('filament.admin.pages.dashboard')
            ->with('success', 'Toko berhasil dipilih!');
    }
    
    public function notAssigned()
    {
        return view('store-not-assigned');
    }
}

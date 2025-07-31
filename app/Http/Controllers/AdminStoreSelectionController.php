<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminStoreSelectionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Jika belum login, redirect ke login
        if (!$user) {
            return redirect('/admin/login');
        }
        
        // Jika bukan super admin, redirect ke dashboard
        if (!$user->isSuperAdmin()) {
            return redirect('/');
        }
        
        // Jika sudah ada selected store, redirect ke dashboard
        if (session('selected_store_id')) {
            return redirect('/');
        }
        
        $stores = Store::all();
        
        return view('admin.store-selection', compact('stores'));
    }
    
    public function select(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id'
        ]);
        
        // Simpan selected store ke session
        Session::put('selected_store_id', $request->store_id);
        
        return redirect('/')->with('success', 'Toko berhasil dipilih!');
    }
}

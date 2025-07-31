<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    /**
     * Clear selected store dan redirect ke store selection
     */
    public function changeStore(Request $request): RedirectResponse
    {
        // Clear selected store dari session
        session()->forget('selected_store_id');
        
        // Redirect ke halaman store selection
        return redirect()->route('admin.store-selection');
    }
}

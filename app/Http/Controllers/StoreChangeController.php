<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StoreChangeController extends Controller
{
    public function changeStore(Request $request)
    {
        // Clear selected store from session
        Session::forget('selected_store_id');
        
        // Redirect to store selection page
        return redirect('/admin/store-selection');
    }
}

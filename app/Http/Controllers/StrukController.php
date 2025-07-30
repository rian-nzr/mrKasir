<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Setting;

class StrukController extends Controller
{
    public function show($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order_items = OrderProduct::where('order_id', $order->id)->get();
        $setting = Setting::first();

        return view('struk', compact('order', 'order_items', 'setting'));
    }
}

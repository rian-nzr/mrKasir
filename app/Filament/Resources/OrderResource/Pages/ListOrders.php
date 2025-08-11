<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Models\Order;
use Filament\Actions;
use App\Models\Setting;
use App\Models\OrderProduct;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function printOrder($order_id)
    {
        $order = Order::with('paymentMethod')->findOrFail($order_id);
        $items = OrderProduct::with('product')->where('order_id', $order_id)->get();

        $this->dispatch('doPrintReceipt', 
            store: Setting::current(),
            order: $order,
            items: $items,
            date: $order->created_at->format('d-m-Y H:i:s')
        );

    }
}

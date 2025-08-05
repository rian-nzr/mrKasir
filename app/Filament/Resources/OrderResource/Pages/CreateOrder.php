<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\CashierShift;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign cashier shift jika ada yang aktif
        $activeShift = CashierShift::where('store_id', auth()->user()->store_id)
            ->where('status', CashierShift::STATUS_OPEN)
            ->first();

        if ($activeShift) {
            $data['cashier_shift_id'] = $activeShift->id;
        }

        return $data;
    }
}

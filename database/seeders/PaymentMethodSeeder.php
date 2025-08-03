<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            // Cash payment method
            PaymentMethod::firstOrCreate(
                [
                    'name' => 'Cash',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Cash',
                    'image' => 'cash.png',
                    'is_cash' => true,
                    'is_ewallet' => false,
                    'balance' => 0,
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );

            // Bank transfer payment methods
            PaymentMethod::firstOrCreate(
                [
                    'name' => 'BCA',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'BCA',
                    'image' => 'bca.png',
                    'is_cash' => false,
                    'is_ewallet' => false,
                    'balance' => 0,
                    'account_number' => '1234567890',
                    'account_name' => 'Toko ' . $store->name,
                    'bank_name' => 'BCA',
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );

            PaymentMethod::firstOrCreate(
                [
                    'name' => 'Mandiri',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Mandiri',
                    'image' => 'mandiri.png',
                    'is_cash' => false,
                    'is_ewallet' => false,
                    'balance' => 0,
                    'account_number' => '0987654321',
                    'account_name' => 'Toko ' . $store->name,
                    'bank_name' => 'Mandiri',
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );

            // E-wallet payment methods
            PaymentMethod::firstOrCreate(
                [
                    'name' => 'GoPay',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'GoPay',
                    'image' => 'gopay.png',
                    'is_cash' => false,
                    'is_ewallet' => true,
                    'balance' => 0,
                    'account_number' => '081234567890',
                    'account_name' => 'Toko ' . $store->name,
                    'description' => 'E-wallet GoPay',
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );

            PaymentMethod::firstOrCreate(
                [
                    'name' => 'OVO',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'OVO',
                    'image' => 'ovo.png',
                    'is_cash' => false,
                    'is_ewallet' => true,
                    'balance' => 0,
                    'account_number' => '081234567890',
                    'account_name' => 'Toko ' . $store->name,
                    'description' => 'E-wallet OVO',
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );

            PaymentMethod::firstOrCreate(
                [
                    'name' => 'DANA',
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'DANA',
                    'image' => 'dana.png',
                    'is_cash' => false,
                    'is_ewallet' => true,
                    'balance' => 0,
                    'account_number' => '081234567890',
                    'account_name' => 'Toko ' . $store->name,
                    'description' => 'E-wallet DANA',
                    'is_active' => true,
                    'store_id' => $store->id,
                ]
            );
        }
    }
}
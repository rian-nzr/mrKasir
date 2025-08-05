<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductGroup;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            // Get categories and groups for this store
            $makananCategory = Category::where('name', 'Makanan')->where('store_id', $store->id)->first();
            $minumanCategory = Category::where('name', 'Minuman')->where('store_id', $store->id)->first();
            $elektronikCategory = Category::where('name', 'Elektronik')->where('store_id', $store->id)->first();
            
            $makananGroup = ProductGroup::where('name', 'Makanan & Minuman')->where('store_id', $store->id)->first();
            $elektronikGroup = ProductGroup::where('name', 'Elektronik')->where('store_id', $store->id)->first();

            // Sample products
            $products = [
                [
                    'name' => 'Nasi Gudeg',
                    'price' => 15000,
                    'cost_price' => 10000,
                    'stock' => 20,
                    'category_id' => $makananCategory?->id,
                    'group_id' => $makananGroup?->id,
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Es Teh Manis',
                    'price' => 5000,
                    'cost_price' => 2000,
                    'stock' => 50,
                    'category_id' => $minumanCategory?->id,
                    'group_id' => $makananGroup?->id,
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Charger Samsung',
                    'price' => 35000,
                    'cost_price' => 25000,
                    'stock' => 10,
                    'category_id' => $elektronikCategory?->id,
                    'group_id' => $elektronikGroup?->id,
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Kopi Hitam',
                    'price' => 8000,
                    'cost_price' => 4000,
                    'stock' => 30,
                    'category_id' => $minumanCategory?->id,
                    'group_id' => $makananGroup?->id,
                    'store_id' => $store->id,
                ],
                [
                    'name' => 'Headset Bluetooth',
                    'price' => 150000,
                    'cost_price' => 100000,
                    'stock' => 5,
                    'category_id' => $elektronikCategory?->id,
                    'group_id' => $elektronikGroup?->id,
                    'store_id' => $store->id,
                ],
            ];

            foreach ($products as $productData) {
                Product::firstOrCreate(
                    [
                        'name' => $productData['name'],
                        'store_id' => $store->id,
                    ],
                    $productData
                );
            }
        }
    }
}

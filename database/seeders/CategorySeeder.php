<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        $categories = [
            'Makanan',
            'Minuman',
            'Snack',
            'Elektronik',
            'Fashion',
            'Kesehatan',
            'Kecantikan',
            'ATK',
            'Pulsa & Paket Data',
            'Token Listrik',
        ];

        foreach ($stores as $store) {
            foreach ($categories as $categoryName) {
                Category::firstOrCreate(
                    [
                        'name' => $categoryName,
                        'store_id' => $store->id,
                    ],
                    [
                        'name' => $categoryName,
                        'store_id' => $store->id,
                    ]
                );
            }
        }
    }
}

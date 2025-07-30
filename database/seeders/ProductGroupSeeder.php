<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        $groups = [
            [
                'name' => 'Makanan & Minuman',
                'description' => 'Produk makanan dan minuman seperti snack, es krim, dll',
            ],
            [
                'name' => 'Elektronik',
                'description' => 'Produk elektronik seperti handphone, charger, headset, dll',
            ],
            [
                'name' => 'Fashion & Aksesoris',
                'description' => 'Produk fashion seperti baju, tas, jam tangan, dll',
            ],
            [
                'name' => 'Kesehatan & Kecantikan',
                'description' => 'Produk kesehatan dan kecantikan seperti obat, vitamin, kosmetik, dll',
            ],
            [
                'name' => 'ATK & Perlengkapan',
                'description' => 'Alat tulis kantor dan perlengkapan seperti pulpen, buku, map, dll',
            ],
        ];

        foreach ($stores as $store) {
            foreach ($groups as $group) {
                // Cek apakah grup sudah ada untuk store ini
                $existing = ProductGroup::where('name', $group['name'])
                    ->where('store_id', $store->id)
                    ->first();
                
                if (!$existing) {
                    ProductGroup::create([
                        'name' => $group['name'],
                        'description' => $group['description'],
                        'store_id' => $store->id,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}

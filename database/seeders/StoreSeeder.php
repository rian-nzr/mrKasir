<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::firstOrCreate(
            ['code' => 'TOKO-A'],
            [
                'name' => 'Toko A',
                'code' => 'TOKO-A',
                'address' => 'Jl. Contoh No. 1, Jakarta',
                'phone' => '021-1234567',
                'email' => 'tokoa@example.com',
                'is_active' => true,
            ]
        );

        Store::firstOrCreate(
            ['code' => 'TOKO-B'],
            [
                'name' => 'Toko B',
                'code' => 'TOKO-B',
                'address' => 'Jl. Contoh No. 2, Bandung',
                'phone' => '022-7654321',
                'email' => 'tokob@example.com',
                'is_active' => true,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tokoA = Store::where('code', 'TOKO-A')->first();
        $tokoB = Store::where('code', 'TOKO-B')->first();

        // Create Super Admin (can access all stores)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'store_id' => null, // Super admin tidak terikat dengan toko tertentu
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Create Kasir for Toko A
        $kasirTokoA = User::firstOrCreate(
            ['email' => 'kasir.tokoa@example.com'],
            [
                'name' => 'Kasir Toko A',
                'password' => Hash::make('password'),
                'store_id' => $tokoA->id,
            ]
        );
        $kasirTokoA->assignRole('kasir');

        // Create Kasir for Toko B
        $kasirTokoB = User::firstOrCreate(
            ['email' => 'kasir.tokob@example.com'],
            [
                'name' => 'Kasir Toko B',
                'password' => Hash::make('password'),
                'store_id' => $tokoB->id,
            ]
        );
        $kasirTokoB->assignRole('kasir');

        // Create Admin for Toko A
        $adminTokoA = User::firstOrCreate(
            ['email' => 'admin.tokoa@example.com'],
            [
                'name' => 'Admin Toko A',
                'password' => Hash::make('password'),
                'store_id' => $tokoA->id,
            ]
        );
        $adminTokoA->assignRole('admin');
    }
}

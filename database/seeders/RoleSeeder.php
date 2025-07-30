<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $kasir = Role::firstOrCreate(['name' => 'kasir']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // Create permissions (sesuaikan dengan kebutuhan)
        $permissions = [
            'view_all_stores',
            'manage_stores',
            'view_products',
            'manage_products',
            'view_orders',
            'manage_orders',
            'view_categories',
            'manage_categories',
            'view_users',
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdmin->givePermissionTo(Permission::all());
        
        $kasir->givePermissionTo([
            'view_products',
            'view_orders',
            'manage_orders',
            'view_categories',
        ]);

        $admin->givePermissionTo([
            'view_products',
            'manage_products',
            'view_orders',
            'manage_orders',
            'view_categories',
            'manage_categories',
        ]);
    }
}

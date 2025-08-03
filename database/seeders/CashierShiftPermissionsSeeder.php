<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CashierShiftPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create cashier shift permissions
        $permissions = [
            'view_cashier_shift',
            'create_cashier_shift',
            'edit_cashier_shift',
            'delete_cashier_shift',
            'start_cashier_shift',
            'close_cashier_shift',
            'view_shift_summary',
            'manage_cash_out',
            'view_daily_report',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('super_admin');
        $kasir = Role::findByName('kasir');
        $admin = Role::findByName('admin');

        // Super admin gets ALL permissions (including new ones)
        $superAdmin->givePermissionTo(Permission::all());

        // Kasir gets basic shift permissions
        $kasir->givePermissionTo([
            'view_cashier_shift',
            'start_cashier_shift',
            'close_cashier_shift',
            'view_shift_summary',
            'manage_cash_out',
        ]);

        // Admin gets management permissions
        $admin->givePermissionTo([
            'view_cashier_shift',
            'create_cashier_shift',
            'edit_cashier_shift',
            'view_shift_summary',
            'view_daily_report',
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixSuperAdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Additional permissions that might be used by policies
        $additionalPermissions = [
            // Store permissions (from policy)
            'view_any_store',
            'create_store',
            'update_store',
            'delete_store',
            
            // User permissions (from policy)
            'view_any_user',
            'create_user',
            'update_user',
            'delete_user',
            
            // Product permissions
            'view_any_product',
            'create_product',
            'update_product',
            'delete_product',
            
            // Category permissions
            'view_any_category',
            'create_category',
            'update_category',
            'delete_category',
            
            // Order permissions
            'view_any_order',
            'create_order',
            'update_order',
            'delete_order',
            
            // Payment Method permissions
            'view_any_payment_method',
            'create_payment_method',
            'update_payment_method',
            'delete_payment_method',
            
            // Report permissions
            'view_any_report',
            'create_report',
            'update_report',
            'delete_report',
            
            // Expense permissions
            'view_any_expense',
            'create_expense',
            'update_expense',
            'delete_expense',
            
            // Role permissions
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            
            // Setting permissions
            'view_any_setting',
            'create_setting',
            'update_setting',
            'delete_setting',
            
            // Transaction permissions
            'view_any_transaction',
            'create_transaction',
            'update_transaction',
            'delete_transaction',
            
            // Product Group permissions
            'view_any_product_group',
            'create_product_group',
            'update_product_group',
            'delete_product_group',
            
            // Cashier Shift permissions
            'view_any_cashier_shift',
            'create_cashier_shift',
            'update_cashier_shift',
            'delete_cashier_shift',
            
            // Special super admin permissions
            'access_all_stores',
            'bypass_store_restrictions',
            'super_admin_access',
            'view_all_data',
            'manage_all_data',
        ];

        // Create all additional permissions
        foreach ($additionalPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Get super admin role
        $superAdmin = Role::findByName('super_admin');
        
        // Give super admin ALL permissions (existing + new)
        $superAdmin->givePermissionTo(Permission::all());
        
        // Also sync permissions to make sure
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('Super Admin now has ' . Permission::count() . ' permissions');
    }
}

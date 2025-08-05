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

        // Create comprehensive permissions
        $permissions = [
            // Store permissions
            'view_all_stores',
            'manage_stores',
            'create_stores',
            'edit_stores',
            'delete_stores',
            
            // Product permissions
            'view_products',
            'manage_products',
            'create_products',
            'edit_products',
            'delete_products',
            
            // Order/Transaction permissions
            'view_orders',
            'manage_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'view_transactions',
            'manage_transactions',
            
            // Category permissions
            'view_categories',
            'manage_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            
            // User permissions
            'view_users',
            'manage_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Payment Method permissions
            'view_payment_methods',
            'manage_payment_methods',
            'create_payment_methods',
            'edit_payment_methods',
            'delete_payment_methods',
            
            // Product Group permissions
            'view_product_groups',
            'manage_product_groups',
            'create_product_groups',
            'edit_product_groups',
            'delete_product_groups',
            
            // Report permissions
            'view_reports',
            'manage_reports',
            'export_reports',
            
            // Expense permissions
            'view_expenses',
            'manage_expenses',
            'create_expenses',
            'edit_expenses',
            'delete_expenses',
            
            // Role & Permission management
            'view_roles',
            'manage_roles',
            'assign_roles',
            'view_permissions',
            'manage_permissions',
            
            // Dashboard permissions
            'view_dashboard',
            'view_analytics',
            
            // System permissions
            'manage_settings',
            'view_system_logs',
            'backup_database',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign ALL permissions to super admin
        $superAdmin->givePermissionTo(Permission::all());
        
        // Kasir gets basic operational permissions
        $kasir->givePermissionTo([
            'view_products',
            'view_orders',
            'manage_orders',
            'create_orders',
            'view_categories',
            'view_payment_methods',
            'view_dashboard',
        ]);

        // Admin gets management permissions but not system-level
        $admin->givePermissionTo([
            'view_products',
            'manage_products',
            'create_products',
            'edit_products',
            'view_orders',
            'manage_orders',
            'create_orders',
            'edit_orders',
            'view_categories',
            'manage_categories',
            'create_categories',
            'edit_categories',
            'view_users',
            'view_payment_methods',
            'manage_payment_methods',
            'view_product_groups',
            'manage_product_groups',
            'view_reports',
            'view_expenses',
            'manage_expenses',
            'view_dashboard',
            'view_analytics',
        ]);
    }
}

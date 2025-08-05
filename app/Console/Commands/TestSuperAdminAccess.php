<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSuperAdminAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:superadmin-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test super admin access to all stores data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        
        if (!$superAdmin) {
            $this->error('Super admin not found!');
            return Command::FAILURE;
        }
        
        // Login as super admin
        Auth::login($superAdmin);
        
        $this->info("🔐 Logged in as: {$superAdmin->name}");
        $this->info("🎭 Role: " . $superAdmin->roles->first()->name);
        $this->info("🏪 Store restriction: " . ($superAdmin->store_id ? "Store ID {$superAdmin->store_id}" : "None (All stores)"));
        $this->newLine();
        
        // Test access to stores
        $this->info("🏪 Store Access Test:");
        $stores = Store::all();
        $this->info("  Total stores accessible: {$stores->count()}");
        foreach ($stores as $store) {
            $this->line("    • {$store->name} (ID: {$store->id})");
        }
        $this->newLine();
        
        // Test access to products from all stores
        $this->info("📦 Product Access Test:");
        $products = Product::with('store')->get();
        $this->info("  Total products accessible: {$products->count()}");
        $productsByStore = $products->groupBy('store.name');
        foreach ($productsByStore as $storeName => $storeProducts) {
            $this->info("    📍 {$storeName}: {$storeProducts->count()} products");
            foreach ($storeProducts as $product) {
                $this->line("      • {$product->name} (Rp " . number_format($product->price) . ")");
            }
        }
        $this->newLine();
        
        // Test access to payment methods from all stores
        $this->info("💳 Payment Method Access Test:");
        $paymentMethods = PaymentMethod::with('store')->get();
        $this->info("  Total payment methods accessible: {$paymentMethods->count()}");
        $paymentsByStore = $paymentMethods->groupBy('store.name');
        foreach ($paymentsByStore as $storeName => $storeMethods) {
            $this->info("    📍 {$storeName}: {$storeMethods->count()} payment methods");
            foreach ($storeMethods as $method) {
                $this->line("      • {$method->name}");
            }
        }
        $this->newLine();
        
        // Test access to categories from all stores
        $this->info("🏷️ Category Access Test:");
        $categories = Category::with('store')->get();
        $this->info("  Total categories accessible: {$categories->count()}");
        $categoriesByStore = $categories->groupBy('store.name');
        foreach ($categoriesByStore as $storeName => $storeCategories) {
            $this->info("    📍 {$storeName}: {$storeCategories->count()} categories");
        }
        $this->newLine();
        
        // Test store-specific query with session
        $this->info("🎯 Store-Specific Query Test:");
        foreach ($stores as $store) {
            session(['selected_store_id' => $store->id]);
            $storeProducts = Product::get();
            $this->line("  • With {$store->name} selected: {$storeProducts->count()} products visible");
        }
        
        // Clear session and test again
        session()->forget('selected_store_id');
        $allProducts = Product::get();
        $this->line("  • Without store selection: {$allProducts->count()} products visible");
        $this->newLine();
        
        // Test permissions
        $this->info("🔑 Permission Test:");
        $testPermissions = [
            'view_all_stores',
            'access_all_stores',
            'super_admin_access',
            'bypass_store_restrictions',
            'view_products',
            'manage_products',
            'view_users',
            'manage_users',
        ];
        
        foreach ($testPermissions as $permission) {
            $hasPermission = $superAdmin->can($permission);
            $icon = $hasPermission ? '✅' : '❌';
            $this->line("  {$icon} {$permission}");
        }
        $this->newLine();
        
        // Summary
        $this->info("📊 Access Summary:");
        $this->info("  ✅ Super Admin can access all stores: " . ($stores->count() === Store::count() ? 'YES' : 'NO'));
        $this->info("  ✅ Super Admin can see all products: " . ($products->count() === Product::withoutGlobalScopes()->count() ? 'YES' : 'NO'));
        $this->info("  ✅ Super Admin can see all payment methods: " . ($paymentMethods->count() === PaymentMethod::withoutGlobalScopes()->count() ? 'YES' : 'NO'));
        $this->info("  ✅ Super Admin has all permissions: " . ($superAdmin->getAllPermissions()->count() > 100 ? 'YES' : 'NO'));
        
        return Command::SUCCESS;
    }
}

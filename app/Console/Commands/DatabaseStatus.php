<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Store;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductGroup;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show current database status and seeded data count';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Current Database Status');
        $this->newLine();

        // Basic counts
        $data = [
            ['Model', 'Count', 'Status'],
            ['Users', User::count(), $this->getStatus(User::count(), 3)],
            ['Stores', Store::count(), $this->getStatus(Store::count(), 2)],
            ['Roles', Role::count(), $this->getStatus(Role::count(), 3)],
            ['Permissions', Permission::count(), $this->getStatus(Permission::count(), 15)],
            ['Payment Methods', PaymentMethod::count(), $this->getStatus(PaymentMethod::count(), 12)],
            ['Product Groups', ProductGroup::count(), $this->getStatus(ProductGroup::count(), 10)],
            ['Categories', Category::count(), $this->getStatus(Category::count(), 20)],
            ['Products', Product::count(), $this->getStatus(Product::count(), 10)],
        ];

        $this->table($data[0], array_slice($data, 1));

        $this->newLine();
        
        // Show stores with their data
        $this->info('📍 Stores Overview:');
        $stores = Store::with(['users', 'paymentMethods', 'products', 'categories', 'productGroups'])->get();
        
        foreach ($stores as $store) {
            $this->info("• {$store->name} ({$store->code})");
            $this->line("  └─ Users: {$store->users->count()}");
            $this->line("  └─ Payment Methods: {$store->paymentMethods->count()}");
            $this->line("  └─ Product Groups: {$store->productGroups->count()}");
            $this->line("  └─ Categories: {$store->categories->count()}");
            $this->line("  └─ Products: {$store->products->count()}");
        }

        $this->newLine();
        
        // Show users with roles
        $this->info('👥 Users Overview:');
        $users = User::with(['roles', 'store'])->get();
        
        foreach ($users as $user) {
            $roleName = $user->roles->first()?->name ?? 'No Role';
            $storeName = $user->store?->name ?? 'All Stores';
            $this->info("• {$user->name} ({$user->email})");
            $this->line("  └─ Role: {$roleName}");
            $this->line("  └─ Store: {$storeName}");
        }

        $this->newLine();
        
        // Database health check
        $this->info('🏥 Database Health Check:');
        $health = [
            'All users have roles' => User::whereDoesntHave('roles')->count() === 0,
            'All stores have payment methods' => Store::whereDoesntHave('paymentMethods')->count() === 0,
            'All stores have categories' => Store::whereDoesntHave('categories')->count() === 0,
            'All products have categories' => Product::whereNull('category_id')->count() === 0,
            'All products have cost price' => Product::whereNull('cost_price')->count() === 0,
        ];

        foreach ($health as $check => $status) {
            $icon = $status ? '✅' : '❌';
            $this->line("{$icon} {$check}");
        }

        return Command::SUCCESS;
    }

    private function getStatus($current, $expected)
    {
        if ($current === 0) {
            return '❌ Empty';
        } elseif ($current >= $expected) {
            return '✅ Good';
        } else {
            return '⚠️  Partial';
        }
    }
}

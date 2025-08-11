#!/bin/bash

# 🧪 QA TESTING AUTOMATION SCRIPT
# Automated setup for POS Mr. Kasir testing environment

echo "🧪 STARTING QA TESTING SETUP FOR MR. KASIR POS"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

print_info "Setting up testing environment..."

# 1. Environment Setup
print_info "Step 1: Setting up environment files"
if [ ! -f ".env.testing" ]; then
    cp .env.example .env.testing
    print_status "Created .env.testing"
else
    print_warning ".env.testing already exists"
fi

# Update .env.testing for testing
cat >> .env.testing << EOL

# Testing Configuration
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=log
EOL

print_status "Updated .env.testing with testing configurations"

# 2. Generate application key for testing
print_info "Step 2: Generating application key"
php artisan key:generate --env=testing
print_status "Application key generated for testing"

# 3. Clear all caches
print_info "Step 3: Clearing caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
print_status "All caches cleared"

# 4. Run migrations and seeders
print_info "Step 4: Setting up database"
php artisan migrate:fresh --seed --env=testing
if [ $? -eq 0 ]; then
    print_status "Database migrated and seeded successfully"
else
    print_error "Database migration failed"
    exit 1
fi

# 5. Setup storage link
print_info "Step 5: Setting up storage"
php artisan storage:link
print_status "Storage link created"

# 6. Setup Filament Shield (if not already done)
print_info "Step 6: Setting up permissions"
php artisan shield:setup --fresh
print_status "Filament Shield permissions setup"

# 7. Create test data
print_info "Step 7: Creating additional test data"

# Create test users programmatically
php artisan tinker --execute="
// Create Super Admin if not exists
if (!\App\Models\User::where('email', 'superadmin@test.com')->exists()) {
    \$user = \App\Models\User::create([
        'name' => 'Super Admin Test',
        'email' => 'superadmin@test.com',
        'password' => bcrypt('password123'),
        'store_id' => null
    ]);
    \$user->assignRole('super_admin');
    echo 'Super Admin Test user created\n';
}

// Create Store Manager for Toko A
if (!\App\Models\User::where('email', 'manager.tokoa@test.com')->exists()) {
    \$store = \App\Models\Store::where('code', 'TOKO-A')->first();
    if (\$store) {
        \$user = \App\Models\User::create([
            'name' => 'Manager Toko A',
            'email' => 'manager.tokoa@test.com',
            'password' => bcrypt('password123'),
            'store_id' => \$store->id
        ]);
        \$user->assignRole('panel_user');
        echo 'Manager Toko A user created\n';
    }
}

// Create Cashier for Toko A
if (!\App\Models\User::where('email', 'cashier.tokoa@test.com')->exists()) {
    \$store = \App\Models\Store::where('code', 'TOKO-A')->first();
    if (\$store) {
        \$user = \App\Models\User::create([
            'name' => 'Cashier Toko A',
            'email' => 'cashier.tokoa@test.com',
            'password' => bcrypt('password123'),
            'store_id' => \$store->id
        ]);
        \$user->assignRole('panel_user');
        echo 'Cashier Toko A user created\n';
    }
}

echo 'Test users creation completed\n';
"

print_status "Test users created"

# 8. Create sample products for testing
print_info "Step 8: Creating sample products"

php artisan tinker --execute="
\$stores = \App\Models\Store::all();

foreach (\$stores as \$store) {
    // Create categories
    \$categories = [
        'Makanan & Minuman',
        'Elektronik',
        'Alat Tulis',
        'Kesehatan'
    ];
    
    foreach (\$categories as \$categoryName) {
        \$category = \App\Models\Category::firstOrCreate([
            'name' => \$categoryName,
            'store_id' => \$store->id
        ]);
    }
    
    // Create products
    \$products = [
        ['name' => 'Buku Tulis', 'price' => 5000, 'stock' => 50, 'category' => 'Alat Tulis'],
        ['name' => 'Pulpen Biru', 'price' => 3000, 'stock' => 100, 'category' => 'Alat Tulis'],
        ['name' => 'Air Mineral', 'price' => 2000, 'stock' => 200, 'category' => 'Makanan & Minuman'],
        ['name' => 'Kopi Sachet', 'price' => 1500, 'stock' => 150, 'category' => 'Makanan & Minuman'],
        ['name' => 'Paracetamol', 'price' => 8000, 'stock' => 30, 'category' => 'Kesehatan']
    ];
    
    foreach (\$products as \$productData) {
        \$category = \App\Models\Category::where('name', \$productData['category'])
                                        ->where('store_id', \$store->id)
                                        ->first();
        
        \App\Models\Product::firstOrCreate([
            'name' => \$productData['name'] . ' - ' . \$store->name,
            'price' => \$productData['price'],
            'stock' => \$productData['stock'],
            'category_id' => \$category->id,
            'store_id' => \$store->id,
            'is_active' => true
        ]);
    }
    
    echo 'Products created for ' . \$store->name . '\n';
}

echo 'Sample products creation completed\n';
"

print_status "Sample products created"

# 9. Create payment methods for each store
print_info "Step 9: Creating payment methods"

php artisan tinker --execute="
\$stores = \App\Models\Store::all();

foreach (\$stores as \$store) {
    \$paymentMethods = [
        ['name' => 'Tunai', 'is_cash' => true, 'is_active' => true],
        ['name' => 'OVO', 'is_cash' => false, 'is_active' => true],
        ['name' => 'GoPay', 'is_cash' => false, 'is_active' => true],
        ['name' => 'DANA', 'is_cash' => false, 'is_active' => true],
        ['name' => 'Transfer Bank', 'is_cash' => false, 'is_active' => true]
    ];
    
    foreach (\$paymentMethods as \$method) {
        \App\Models\PaymentMethod::firstOrCreate([
            'name' => \$method['name'],
            'store_id' => \$store->id
        ], \$method);
    }
    
    echo 'Payment methods created for ' . \$store->name . '\n';
}

echo 'Payment methods creation completed\n';
"

print_status "Payment methods created"

# 10. Optimize for testing
print_info "Step 10: Optimizing for testing"
php artisan config:cache
php artisan route:cache
print_status "Configuration cached for testing"

# 11. Display test information
echo ""
echo "🎉 QA TESTING SETUP COMPLETED!"
echo "=============================="
echo ""
print_info "Test Environment Details:"
echo "  - Environment: testing"
echo "  - Database: SQLite (in memory for faster testing)"
echo "  - Cache: Array driver"
echo "  - Queue: Sync driver"
echo ""
print_info "Test Users Created:"
echo "  📧 superadmin@test.com / password123 (Super Admin)"
echo "  📧 manager.tokoa@test.com / password123 (Store Manager)"
echo "  📧 cashier.tokoa@test.com / password123 (Cashier)"
echo ""
print_info "Test Data Created:"
echo "  🏪 2 Stores (existing from seeders)"
echo "  📦 5+ Products per store"
echo "  🏷️ 4 Categories per store"
echo "  💳 5 Payment methods per store"
echo ""
print_info "Quick Test URLs:"
echo "  🌐 Admin Panel: http://127.0.0.1:8000/admin"
echo "  🛒 POS System: http://127.0.0.1:8000/admin/pos"
echo ""
print_warning "IMPORTANT NOTES:"
echo "  1. Start your development server: php artisan serve"
echo "  2. For POS access, login as cashier and open a shift first"
echo "  3. Use the TEST_EXECUTION_GUIDE.md for systematic testing"
echo "  4. Check TESTING_STRUCTURE.md for comprehensive test cases"
echo ""
print_info "Next Steps:"
echo "  1. Run: php artisan serve"
echo "  2. Open browser: http://127.0.0.1:8000"
echo "  3. Follow the test execution guide"
echo ""
print_status "Happy Testing! 🧪✨"

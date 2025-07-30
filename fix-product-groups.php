<?php

// Script untuk fix product groups dan products yang sudah ada
// Jalankan dengan: php fix-product-groups.php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductGroup;
use App\Models\Product;
use Illuminate\Support\Str;

echo "🔧 Memperbaiki ProductGroups dan Products...\n\n";

// 1. Fix ProductGroup slugs yang duplicate
echo "1. Memperbaiki slug ProductGroup...\n";
$groups = ProductGroup::all();
foreach ($groups as $group) {
    $newSlug = ProductGroup::generateUniqueSlug($group->name, $group->store_id);
    if ($group->slug !== $newSlug) {
        $group->update(['slug' => $newSlug]);
        echo "   ✅ Updated: {$group->name} -> {$newSlug}\n";
    }
}

// 2. Tambah cost_price default untuk products yang belum ada
echo "\n2. Menambahkan default cost_price untuk produk...\n";
$products = Product::whereNull('cost_price')->get();
foreach ($products as $product) {
    // Set cost_price sebagai 70% dari harga jual sebagai estimasi
    $estimatedCostPrice = $product->price ? (int)($product->price * 0.7) : 0;
    $product->update(['cost_price' => $estimatedCostPrice]);
    echo "   ✅ Updated: {$product->name} - Cost: " . number_format($estimatedCostPrice) . "\n";
}

// 3. Fix products yang punya group_id invalid
echo "\n3. Memperbaiki relasi group_id produk...\n";
$invalidProducts = Product::whereNotNull('group_id')
    ->whereNotExists(function($query) {
        $query->select('id')
              ->from('product_groups')
              ->whereColumn('product_groups.id', 'products.group_id');
    })->get();

foreach ($invalidProducts as $product) {
    $product->update(['group_id' => null]);
    echo "   ✅ Reset group_id for: {$product->name}\n";
}

echo "\n✨ Selesai! Semua data telah diperbaiki.\n";
echo "📊 Summary:\n";
echo "   - ProductGroups: " . ProductGroup::count() . "\n";
echo "   - Products: " . Product::count() . "\n";
echo "   - Products with cost_price: " . Product::whereNotNull('cost_price')->count() . "\n";
echo "   - Products with group: " . Product::whereNotNull('group_id')->count() . "\n";

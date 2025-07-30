<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_groups', function (Blueprint $table) {
            // Hapus unique constraint lama
            $table->dropUnique(['slug']);
            
            // Tambah unique constraint baru yang combine slug + store_id
            $table->unique(['slug', 'store_id'], 'product_groups_slug_store_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_groups', function (Blueprint $table) {
            // Hapus unique constraint baru
            $table->dropUnique('product_groups_slug_store_unique');
            
            // Kembalikan unique constraint lama
            $table->unique('slug');
        });
    }
};

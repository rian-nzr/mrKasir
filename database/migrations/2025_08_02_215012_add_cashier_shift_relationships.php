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
        // Add cashier_shift_id to orders table if it doesn't exist
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'cashier_shift_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts');
                $table->index('cashier_shift_id');
            });
        }

        // Add cashier_shift_id to transactions table if it doesn't exist
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'cashier_shift_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts');
                $table->index('cashier_shift_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove cashier_shift_id from orders table if it exists
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'cashier_shift_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['cashier_shift_id']);
                $table->dropColumn('cashier_shift_id');
            });
        }

        // Remove cashier_shift_id from transactions table if it exists
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'cashier_shift_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['cashier_shift_id']);
                $table->dropColumn('cashier_shift_id');
            });
        }
    }
};

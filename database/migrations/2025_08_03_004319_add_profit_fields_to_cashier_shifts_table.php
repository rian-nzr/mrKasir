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
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->decimal('total_profit', 15, 2)->nullable()->after('total_discounts');
            $table->decimal('transaction_profit', 15, 2)->nullable()->after('total_profit');
            $table->decimal('order_profit', 15, 2)->nullable()->after('transaction_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['total_profit', 'transaction_profit', 'order_profit']);
        });
    }
};

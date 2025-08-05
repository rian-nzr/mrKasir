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
            // Balance tracking saat buka kasir
            $table->json('opening_balance_snapshot')->nullable()->after('opening_cash');
            $table->decimal('opening_total_balance', 15, 2)->default(0)->after('opening_balance_snapshot');
            
            // Balance tracking saat tutup kasir
            $table->json('closing_balance_snapshot')->nullable()->after('closing_cash');
            $table->decimal('closing_total_balance', 15, 2)->default(0)->after('closing_balance_snapshot');
            
            // Perhitungan otomatis
            $table->decimal('calculated_cash_flow', 15, 2)->default(0)->after('closing_total_balance');
            $table->decimal('expected_total_balance', 15, 2)->default(0)->after('calculated_cash_flow');
            $table->decimal('total_balance_difference', 15, 2)->default(0)->after('expected_total_balance');
            
            // Physical cash counting
            $table->decimal('physical_cash_count', 15, 2)->nullable()->after('total_balance_difference');
            $table->json('cash_denomination_count')->nullable()->after('physical_cash_count');
            $table->decimal('cash_counting_difference', 15, 2)->default(0)->after('cash_denomination_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn([
                'opening_balance_snapshot',
                'opening_total_balance',
                'closing_balance_snapshot', 
                'closing_total_balance',
                'calculated_cash_flow',
                'expected_total_balance',
                'total_balance_difference',
                'physical_cash_count',
                'cash_denomination_count',
                'cash_counting_difference'
            ]);
        });
    }
};

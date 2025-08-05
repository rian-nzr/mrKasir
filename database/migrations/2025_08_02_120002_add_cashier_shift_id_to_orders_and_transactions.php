<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')->nullable()->after('store_id')->constrained()->onDelete('set null');
            $table->index(['cashier_shift_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')->nullable()->after('store_id')->constrained()->onDelete('set null');
            $table->index(['cashier_shift_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropIndex(['cashier_shift_id']);
            $table->dropColumn('cashier_shift_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropIndex(['cashier_shift_id']);
            $table->dropColumn('cashier_shift_id');
        });
    }
};

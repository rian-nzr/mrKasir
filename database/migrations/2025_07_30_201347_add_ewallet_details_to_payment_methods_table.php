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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_ewallet')->default(false)->after('is_cash');
            $table->decimal('balance', 15, 2)->default(0)->after('is_ewallet');
            $table->string('account_number')->nullable()->after('balance');
            $table->string('account_name')->nullable()->after('account_number');
            $table->string('bank_name')->nullable()->after('account_name');
            $table->text('description')->nullable()->after('bank_name');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'is_ewallet',
                'balance', 
                'account_number',
                'account_name',
                'bank_name',
                'description',
                'is_active'
            ]);
        });
    }
};

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
        Schema::create('payment_method_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('cascade');
            $table->foreignId('to_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('cascade');
            $table->enum('type', ['topup', 'withdraw', 'transfer_out', 'transfer_in']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_number')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['from_payment_method_id', 'created_at'], 'pmt_from_pm_created_idx');
            $table->index(['to_payment_method_id', 'created_at'], 'pmt_to_pm_created_idx');
            $table->index(['type', 'created_at'], 'pmt_type_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_transactions');
    }
};

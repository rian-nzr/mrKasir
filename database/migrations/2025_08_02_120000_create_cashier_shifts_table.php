<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shift_number')->unique();
            $table->enum('status', ['open', 'closed', 'suspended'])->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->default(0);
            $table->decimal('cash_difference', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_discounts', 15, 2)->default(0);
            $table->decimal('cash_out_amount', 15, 2)->default(0);
            $table->text('cash_out_notes')->nullable();
            $table->json('payment_summary')->nullable();
            $table->json('shift_summary')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['opened_at']);
            $table->index(['closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};

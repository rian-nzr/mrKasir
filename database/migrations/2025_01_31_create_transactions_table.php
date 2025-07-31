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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Jenis transaksi
            $table->enum('type', ['transfer', 'tarik_tunai', 'jasa_transfer', 'mode_pulsa']);
            
            // Data transaksi umum
            $table->decimal('amount', 15, 2); // Jumlah utama transaksi
            $table->decimal('admin_luar', 15, 2)->default(0); // Admin yang masuk ke cash
            $table->decimal('admin_dalam', 15, 2)->default(0); // Admin yang masuk ke sumber dana
            $table->text('keterangan')->nullable();
            
            // Data khusus untuk tipe tertentu
            $table->foreignId('sumber_dana_id')->nullable()->constrained('payment_methods')->onDelete('set null'); // Untuk transfer, tarik tunai, mode pulsa
            $table->string('tujuan')->nullable(); // Untuk tarik tunai
            $table->decimal('terima_dana', 15, 2)->nullable(); // Untuk jasa transfer
            $table->decimal('admin', 15, 2)->nullable(); // Untuk jasa transfer & mode pulsa
            $table->string('jenis_transaksi')->nullable(); // Untuk mode pulsa
            $table->string('sumber')->nullable(); // Untuk mode pulsa
            $table->decimal('modal', 15, 2)->nullable(); // Untuk mode pulsa 
            $table->decimal('harga_jual', 15, 2)->nullable(); // Untuk mode pulsa
            
            // Status dan tracking
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->json('financial_impact')->nullable(); // Log dampak finansial
            
            $table->timestamps();
            
            // Indexes
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

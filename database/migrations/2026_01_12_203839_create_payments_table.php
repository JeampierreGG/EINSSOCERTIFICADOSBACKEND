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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('PEN');
            
            $table->string('proof_image_path')->nullable();
            $table->string('transaction_code')->nullable();
            $table->timestamp('date_paid')->nullable();
            
            $table->json('items')->nullable(); // Detalles de lo comprado
            $table->text('admin_note')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

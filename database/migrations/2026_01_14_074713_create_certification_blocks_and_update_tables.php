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
        Schema::create('certification_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., 'Campaña Lanzamiento', 'Bloque 1'
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('course_certificate_options', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->date('discount_end_date')->nullable();
            $table->foreignId('certification_block_id')->nullable()->constrained('certification_blocks')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('certification_block_id')->nullable()->constrained('certification_blocks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['certification_block_id']);
            $table->dropColumn('certification_block_id');
        });

        Schema::table('course_certificate_options', function (Blueprint $table) {
            $table->dropForeign(['certification_block_id']);
            $table->dropColumn(['discount_percentage', 'discount_end_date', 'certification_block_id']);
        });

        Schema::dropIfExists('certification_blocks');
    }
};

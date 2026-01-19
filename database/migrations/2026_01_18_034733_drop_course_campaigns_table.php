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
        Schema::dropIfExists('course_campaigns');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('course_campaigns', function ($table) {
            $table->id();
            $table->foreignId('course_certificate_option_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('fixed_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};

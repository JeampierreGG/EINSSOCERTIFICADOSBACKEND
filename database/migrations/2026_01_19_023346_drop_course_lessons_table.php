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
        Schema::dropIfExists('course_lessons');
    }

    public function down(): void
    {
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['video', 'pdf', 'zoom', 'link', 'text'])->default('video');
            $table->string('content_url')->nullable(); 
            $table->string('external_url')->nullable(); 
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }
};

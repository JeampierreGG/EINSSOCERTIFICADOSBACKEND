<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allies', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path');         // Ruta del logo en S3
            $table->string('name')->nullable();  // Nombre opcional del aliado (útil para alt text)
            $table->unsignedSmallInteger('sort_order')->default(0); // Para ordenar manualmente
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allies');
    }
};

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
        Schema::create('course_reminder_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // Tipo de recordatorio: 'enrollment', 'opening', 'evaluation', 'evaluation_reminder'
            $table->string('type'); // enrollment | opening | evaluation | evaluation_reminder

            // Si es evaluation o evaluation_reminder, referencia a la evaluación
            $table->foreignId('evaluation_id')->nullable()->constrained('evaluations')->onDelete('cascade');

            // Ruta de la imagen almacenada
            $table->string('image_path')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['course_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_reminder_images');
    }
};

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
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('class_time')->nullable(); // Usamos string para flexibilidad (e.g. "07:00 PM") o time si quisieramos estricto HH:MM:SS. El usuario pidió "Hora de Clase", time picker devuelve string HH:MM:SS usualmente. Filament TimePicker trabaja con string H:i.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn('class_time');
        });
    }
};

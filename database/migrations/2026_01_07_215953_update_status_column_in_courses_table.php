<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Eliminar la restricción CHECK que causa el error
            DB::statement("ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_status_check");
            
            // Cambiar la columna a string para permitir cualquier valor
            $table->string('status')->change();
        });

        // Migrar los estados antiguos a 'published' para que sean consistentes con la nueva lógica
        // La lógica visual ahora depende de las fechas, no de estos estados específicos
        DB::table('courses')
            ->whereIn('status', ['iniciado', 'proximamente', 'finalizado'])
            ->update(['status' => 'published']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // No revertimos estrictamente porque los datos se han transformado
             $table->string('status')->change();
        });
    }
};

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
        // Para PostgreSQL, eliminamos la restricción check y cambiamos el tipo
        DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS courses_level_check');
        DB::statement('ALTER TABLE courses ALTER COLUMN level TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE courses ALTER COLUMN level SET DEFAULT \'Básico\'');

        // Actualizamos los registros existentes de inglés a español
        DB::table('courses')->where('level', 'basic')->update(['level' => 'Básico']);
        DB::table('courses')->where('level', 'intermediate')->update(['level' => 'Intermedio']);
        DB::table('courses')->where('level', 'advanced')->update(['level' => 'Avanzado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a enum es más complejo en raw SQL, simplemente lo dejamos como string 
        // pero con los valores antiguos si es necesario
        DB::table('courses')->where('level', 'Básico')->update(['level' => 'basic']);
        DB::table('courses')->where('level', 'Intermedio')->update(['level' => 'intermediate']);
        DB::table('courses')->where('level', 'Avanzado')->update(['level' => 'advanced']);
    }
};

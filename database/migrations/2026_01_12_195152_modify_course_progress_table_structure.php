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
        // Si queremos mantener datos, sería complejo mapear lecciones a módulos.
        // Asumiremos reinicio de estructura o que la tabla se puede alterar directamente.
        
        // Opción segura: Eliminar columnas viejas, agregar nueva columna.
        Schema::table('course_progress', function (Blueprint $table) {
            // Eliminamos índices si existen
            // $table->dropIndex(['progressable_type', 'progressable_id']); 
            
            $table->dropColumn(['progressable_id', 'progressable_type']);
            
            // Agregamos module_id
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_progress', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn('module_id');
            
            $table->unsignedBigInteger('progressable_id');
            $table->string('progressable_type');
        });
    }
};

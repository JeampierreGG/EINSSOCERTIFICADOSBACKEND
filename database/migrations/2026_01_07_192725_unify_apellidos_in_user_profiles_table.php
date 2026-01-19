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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('apellidos')->nullable()->after('nombres');
        });

        // Migrar datos existentes (concatenar)
        \Illuminate\Support\Facades\DB::statement("UPDATE user_profiles SET apellidos = CONCAT(COALESCE(apellido_paterno, ''), ' ', COALESCE(apellido_materno, ''))");
        \Illuminate\Support\Facades\DB::statement("UPDATE user_profiles SET apellidos = TRIM(apellidos)");

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
        });
        
        // No podemos recuperar exactamente paterno/materno de un string concatenado fácilmente, 
        // pero podemos intentar un split básico como fallback si se revierte.
        // Aquí solo recreamos las columnas.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Asignar slugs a registros existentes
        $slugMap = [
            'Colegio de Ingenieros del Perú' => 'cip',
            'Einsso Consultores'             => 'einsso',
            'Megapack'                       => 'megapack',
        ];

        foreach ($slugMap as $name => $slug) {
            \App\Models\Institution::where('name', $name)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

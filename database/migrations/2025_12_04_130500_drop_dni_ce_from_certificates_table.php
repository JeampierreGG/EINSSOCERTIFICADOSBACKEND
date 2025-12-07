<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('certificates', 'dni_ce')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->dropColumn('dni_ce');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('certificates', 'dni_ce')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->string('dni_ce')->nullable();
            });
        }
    }
};

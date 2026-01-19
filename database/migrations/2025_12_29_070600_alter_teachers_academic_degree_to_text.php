<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE teachers ALTER COLUMN academic_degree TYPE TEXT');
            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            $table->text('academic_degree')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE teachers ALTER COLUMN academic_degree TYPE VARCHAR(255) USING LEFT(academic_degree, 255)');
            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('academic_degree', 255)->nullable()->change();
        });
    }
};


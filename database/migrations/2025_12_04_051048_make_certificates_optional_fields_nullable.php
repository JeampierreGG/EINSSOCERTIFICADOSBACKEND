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
        DB::statement('ALTER TABLE certificates ALTER COLUMN category DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN title DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN hours DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN grade DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN issue_date DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN code DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN file_path DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE certificates ALTER COLUMN category SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN title SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN hours SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN grade SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN issue_date SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN code SET NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN file_path SET NOT NULL');
    }
};

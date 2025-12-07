<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL syntax
        DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password SET NOT NULL');
    }
};

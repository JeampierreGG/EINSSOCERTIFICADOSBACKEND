<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Certificates: change grade to SMALLINT and enforce 0..20
        DB::statement("ALTER TABLE certificates ALTER COLUMN grade TYPE SMALLINT USING NULLIF(grade, '')::SMALLINT");
        DB::statement("ALTER TABLE certificates ADD CONSTRAINT certificates_grade_check CHECK (grade IS NULL OR (grade >= 0 AND grade <= 20))");

        // Certificate items: change grade to SMALLINT and enforce 0..20
        DB::statement("ALTER TABLE certificate_items ALTER COLUMN grade TYPE SMALLINT USING NULLIF(grade, '')::SMALLINT");
        DB::statement("ALTER TABLE certificate_items ADD CONSTRAINT certificate_items_grade_check CHECK (grade IS NULL OR (grade >= 0 AND grade <= 20))");
    }

    public function down(): void
    {
        // Drop constraints and revert to VARCHAR
        DB::statement("ALTER TABLE certificates DROP CONSTRAINT IF EXISTS certificates_grade_check");
        DB::statement("ALTER TABLE certificates ALTER COLUMN grade TYPE VARCHAR(255)");

        DB::statement("ALTER TABLE certificate_items DROP CONSTRAINT IF EXISTS certificate_items_grade_check");
        DB::statement("ALTER TABLE certificate_items ALTER COLUMN grade TYPE VARCHAR(255)");
    }
};

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
        // PostgreSQL requires dropping and recreating the constraint
        DB::statement("ALTER TABLE course_enrollments DROP CONSTRAINT IF EXISTS course_enrollments_status_check");
        DB::statement("ALTER TABLE course_enrollments ADD CONSTRAINT course_enrollments_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'completed'::character varying, 'dropped'::character varying, 'inactive'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE course_enrollments DROP CONSTRAINT IF EXISTS course_enrollments_status_check");
        DB::statement("ALTER TABLE course_enrollments ADD CONSTRAINT course_enrollments_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'completed'::character varying, 'dropped'::character varying]::text[]))");
    }
};

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
        DB::statement("DROP VIEW IF EXISTS course_contents_view");
        
        DB::statement('
            CREATE VIEW course_contents_view AS
            SELECT 
                CONCAT(\'module-\', id) as id,
                id as source_id,
                \'module\' as type,
                course_id,
                title,
                "order",
                created_at,
                updated_at
            FROM course_modules
            UNION ALL
            SELECT 
                CONCAT(\'evaluation-\', id) as id,
                id as source_id,
                \'evaluation\' as type,
                course_id,
                title,
                "order",
                created_at,
                updated_at
            FROM evaluations
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS course_contents_view");
    }
};

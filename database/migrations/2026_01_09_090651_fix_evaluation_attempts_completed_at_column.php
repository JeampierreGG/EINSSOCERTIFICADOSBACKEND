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
        Schema::disableForeignKeyConstraints();
        
        Schema::dropIfExists('evaluation_answers');
        Schema::dropIfExists('evaluation_attempts');

        // Recreate attempts with CORRECT completed_at (nullable)
        Schema::create('evaluation_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Assuming evaluation_id maps to 'evaluations' table, but constrained() infers 'evaluations' from 'evaluation_id' usually.
            // If table name is 'evaluations', this works. If not, we might need explicit table name.
            // Standard laravel: model Evaluation -> table evaluations.
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable()->default(null); // FIXED
            $table->timestamps();
        });

        // Recreate answers with CORRECTED TYPO column name matching the code
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            // Usage of 'evaluation_attemps_id' (with typo) to match the code adjustments requested previously
            // Note: constrained('evaluation_attempts') links to the correct table
            $table->foreignId('evaluation_attemps_id')->constrained('evaluation_attempts')->onDelete('cascade');
            $table->foreignId('evaluation_question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->foreignId('evaluation_option_id')->nullable()->constrained('evaluation_options')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('evaluation_answers');
        Schema::dropIfExists('evaluation_attempts');
        Schema::enableForeignKeyConstraints();
    }
};

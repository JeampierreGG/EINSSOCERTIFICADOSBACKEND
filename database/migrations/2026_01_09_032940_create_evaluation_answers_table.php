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
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_attempt_id')->constrained('evaluation_attempts')->onDelete('cascade');
            // Assuming evaluation_questions table exists; using conventional name or explicit if needed.
            // Based on models: EvaluationQuestion -> table usually evaluation_questions
            $table->foreignId('evaluation_question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->foreignId('evaluation_option_id')->nullable()->constrained('evaluation_options')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};

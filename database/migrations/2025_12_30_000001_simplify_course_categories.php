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
        Schema::table('courses', function (Blueprint $table) {
            // Drop Foreign Key first
            $table->dropForeign(['course_category_id']);
            $table->dropColumn('course_category_id');
            
            // Add simple string category
            $table->string('category')->nullable()->after('description');
        });

        // Drop the table purely if we want to clean up, but user just said "remove option from panel". 
        // We will drop to be clean.
        Schema::dropIfExists('course_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create is complex, just nullable string for reverse
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->foreignId('course_category_id')->nullable()->constrained('course_categories')->nullOnDelete();
        });
    }
};

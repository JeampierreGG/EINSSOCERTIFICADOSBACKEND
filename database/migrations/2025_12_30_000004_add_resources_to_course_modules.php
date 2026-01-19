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
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('enable_date');
            $table->string('zoom_url')->nullable()->after('pdf_path');
            $table->string('video_url')->nullable()->after('zoom_url'); // YouTube link
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'zoom_url', 'video_url']);
        });
    }
};

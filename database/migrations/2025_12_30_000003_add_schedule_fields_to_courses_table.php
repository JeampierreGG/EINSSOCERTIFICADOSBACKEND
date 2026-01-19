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
            $table->integer('sessions_count')->nullable()->after('duration_text');
            $table->string('class_type')->nullable()->after('sessions_count'); // 'synchronous', 'asynchronous', 'mixed'
            $table->json('class_schedules')->nullable()->after('class_type'); // Stores array of {day, time}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['sessions_count', 'class_type', 'class_schedules']);
        });
    }
};

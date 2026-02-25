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
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->boolean('course_opening_sent')->default(false)->after('status');
        });

        Schema::create('evaluation_notifications_sent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');
            $table->string('notification_type'); // 'start', 'reminder'
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['enrollment_id', 'evaluation_id', 'notification_type'], 'idx_enroll_eval_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_notifications_sent');
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn('course_opening_sent');
        });
    }
};

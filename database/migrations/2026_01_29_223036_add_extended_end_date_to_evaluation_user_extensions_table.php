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
        Schema::table('evaluation_user_extensions', function (Blueprint $table) {
            $table->dateTime('extended_end_date')->nullable()->after('extra_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_user_extensions', function (Blueprint $table) {
            $table->dropColumn('extended_end_date');
        });
    }
};

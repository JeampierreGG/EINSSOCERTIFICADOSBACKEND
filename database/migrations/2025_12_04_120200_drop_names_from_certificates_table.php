<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('certificates', 'last_name')) {
                $table->dropColumn('last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('certificates', 'last_name')) {
                $table->string('last_name')->nullable();
            }
        });
    }
};


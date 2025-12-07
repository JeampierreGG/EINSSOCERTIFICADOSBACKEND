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
        Schema::table('certificate_items', function (Blueprint $table) {
            $table->foreignId('institution_id')
                ->after('certificate_id')
                ->constrained('institutions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};

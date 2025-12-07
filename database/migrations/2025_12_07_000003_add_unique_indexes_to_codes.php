<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existsCert = false;
        try {
            $existsCert = !empty(DB::select("SELECT 1 FROM pg_constraint WHERE conname = 'certificates_code_unique'"));
        } catch (\Throwable $e) { }
        Schema::table('certificates', function (Blueprint $table) use ($existsCert) {
            if (!$existsCert && Schema::hasColumn('certificates', 'code')) {
                $table->unique('code');
            }
        });

        // Skip altering certificate_items due to existing duplicates; validate uniqueness at form level.
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            try { $table->dropUnique(['code']); } catch (\Throwable $e) { }
        });

        Schema::table('certificate_items', function (Blueprint $table) {
            try { $table->dropUnique(['code']); } catch (\Throwable $e) { }
        });
    }
};

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
        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'first_name')) {
                $table->string('first_name')->after('id');
            }
            if (! Schema::hasColumn('certificates', 'last_name')) {
                $table->string('last_name');
            }
            if (! Schema::hasColumn('certificates', 'dni_ce')) {
                $table->string('dni_ce');
            }
            if (! Schema::hasColumn('certificates', 'type')) {
                $table->enum('type', ['solo', 'megapack']);
            }
            if (! Schema::hasColumn('certificates', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('certificates', 'category')) {
                $table->enum('category', ['curso', 'modular', 'diplomado'])->nullable();
            }
            if (! Schema::hasColumn('certificates', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'hours')) {
                $table->unsignedInteger('hours')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'grade')) {
                $table->string('grade')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'issue_date')) {
                $table->date('issue_date')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'code')) {
                $table->string('code')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'file_path')) {
                $table->string('file_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'first_name')) $table->dropColumn('first_name');
            if (Schema::hasColumn('certificates', 'last_name')) $table->dropColumn('last_name');
            if (Schema::hasColumn('certificates', 'dni_ce')) $table->dropColumn('dni_ce');
            if (Schema::hasColumn('certificates', 'type')) $table->dropColumn('type');
            if (Schema::hasColumn('certificates', 'category')) $table->dropColumn('category');
            if (Schema::hasColumn('certificates', 'title')) $table->dropColumn('title');
            if (Schema::hasColumn('certificates', 'hours')) $table->dropColumn('hours');
            if (Schema::hasColumn('certificates', 'grade')) $table->dropColumn('grade');
            if (Schema::hasColumn('certificates', 'issue_date')) $table->dropColumn('issue_date');
            if (Schema::hasColumn('certificates', 'code')) $table->dropColumn('code');
            if (Schema::hasColumn('certificates', 'file_path')) $table->dropColumn('file_path');
            if (Schema::hasColumn('certificates', 'institution_id')) $table->dropConstrainedForeignId('institution_id');
        });
    }
};

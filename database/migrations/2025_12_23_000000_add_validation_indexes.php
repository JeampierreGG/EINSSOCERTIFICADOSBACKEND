<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'user_id') && Schema::hasColumn('certificates', 'issue_date')) {
                try {
                    $table->index(['user_id', 'issue_date'], 'certificates_user_issue_date_index');
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('certificate_items', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_items', 'certificate_id')) {
                try {
                    $table->index(['certificate_id'], 'certificate_items_certificate_id_index');
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasColumn('certificate_items', 'institution_id')) {
                try {
                    $table->index(['institution_id'], 'certificate_items_institution_id_index');
                } catch (\Throwable $e) {
                }
            }
        });

        if ($driver === 'sqlsrv') {
            DB::statement("
                IF COL_LENGTH('certificates', 'code_norm') IS NULL
                BEGIN
                    ALTER TABLE certificates
                    ADD code_norm AS LOWER(LTRIM(RTRIM([code]))) PERSISTED
                END
            ");

            DB::statement("
                IF NOT EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'IX_certificates_code_norm'
                      AND object_id = OBJECT_ID('certificates')
                )
                BEGIN
                    CREATE INDEX IX_certificates_code_norm
                    ON certificates(code_norm)
                END
            ");

            DB::statement("
                IF COL_LENGTH('certificate_items', 'code_norm') IS NULL
                BEGIN
                    ALTER TABLE certificate_items
                    ADD code_norm AS LOWER(LTRIM(RTRIM([code]))) PERSISTED
                END
            ");

            DB::statement("
                IF NOT EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'IX_certificate_items_code_norm'
                      AND object_id = OBJECT_ID('certificate_items')
                )
                BEGIN
                    CREATE INDEX IX_certificate_items_code_norm
                    ON certificate_items(code_norm)
                END
            ");
        } else {
            Schema::table('certificates', function (Blueprint $table) {
                if (Schema::hasColumn('certificates', 'code')) {
                    try {
                        $table->index(['code'], 'certificates_code_index');
                    } catch (\Throwable $e) {
                    }
                }
            });

            Schema::table('certificate_items', function (Blueprint $table) {
                if (Schema::hasColumn('certificate_items', 'code')) {
                    try {
                        $table->index(['code'], 'certificate_items_code_index');
                    } catch (\Throwable $e) {
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("
                IF EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'IX_certificates_code_norm'
                      AND object_id = OBJECT_ID('certificates')
                )
                DROP INDEX IX_certificates_code_norm ON certificates
            ");

            DB::statement("
                IF COL_LENGTH('certificates', 'code_norm') IS NOT NULL
                ALTER TABLE certificates DROP COLUMN code_norm
            ");

            DB::statement("
                IF EXISTS (
                    SELECT 1
                    FROM sys.indexes
                    WHERE name = 'IX_certificate_items_code_norm'
                      AND object_id = OBJECT_ID('certificate_items')
                )
                DROP INDEX IX_certificate_items_code_norm ON certificate_items
            ");

            DB::statement("
                IF COL_LENGTH('certificate_items', 'code_norm') IS NOT NULL
                ALTER TABLE certificate_items DROP COLUMN code_norm
            ");
        } else {
            Schema::table('certificates', function (Blueprint $table) {
                try {
                    $table->dropIndex('certificates_code_index');
                } catch (\Throwable $e) {
                }
            });

            Schema::table('certificate_items', function (Blueprint $table) {
                try {
                    $table->dropIndex('certificate_items_code_index');
                } catch (\Throwable $e) {
                }
            });
        }

        Schema::table('certificates', function (Blueprint $table) {
            try {
                $table->dropIndex('certificates_user_issue_date_index');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('certificate_items', function (Blueprint $table) {
            try {
                $table->dropIndex('certificate_items_certificate_id_index');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('certificate_items_institution_id_index');
            } catch (\Throwable $e) {
            }
        });
    }
};


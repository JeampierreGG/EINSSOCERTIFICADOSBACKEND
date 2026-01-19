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
        Schema::table('course_certificate_options', function (Blueprint $table) {
            $table->string('type')->default('solo_certificado')->after('course_id');
            $table->string('image_1_path')->nullable()->after('price');
            $table->string('image_2_path')->nullable()->after('image_1_path');
            $table->json('megapack_items')->nullable()->after('image_2_path');
            $table->text('details')->nullable()->after('megapack_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_certificate_options', function (Blueprint $table) {
            $table->dropColumn(['type', 'image_1_path', 'image_2_path', 'megapack_items', 'details']);
        });
    }
};

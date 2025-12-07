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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('dni_ce');
            $table->enum('type', ['solo', 'megapack']);
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->enum('category', ['curso', 'modular', 'diplomado']);
            $table->string('title');
            $table->unsignedInteger('hours')->nullable();
            $table->string('grade')->nullable();
            $table->date('issue_date');
            $table->string('code')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};

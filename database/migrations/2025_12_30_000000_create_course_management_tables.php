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
        // 1. Course Categories
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 2. Courses
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('brochure_path')->nullable(); // File upload for brochure
            $table->string('whatsapp_number')->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('duration_text')->nullable(); // e.g. "4 semanas"
            
            $table->enum('level', ['basic', 'intermediate', 'advanced'])->default('basic');
            $table->enum('status', ['proximamente', 'iniciado', 'finalizado'])->default('proximamente');
            
            $table->boolean('is_free')->default(false);
            $table->decimal('price', 10, 2)->nullable(); // Null if free, or 0.00
            
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('course_category_id')->nullable()->constrained('course_categories')->nullOnDelete();
            
            $table->timestamps();
        });

        // 3. Course Modules
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->integer('order')->default(0);
            $table->dateTime('enable_date')->nullable(); // For scheduling access
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 4. Course Lessons
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['video', 'pdf', 'zoom', 'link', 'text'])->default('video');
            $table->string('content_url')->nullable(); // For video/pdf path
            $table->string('external_url')->nullable(); // For zoom/forms/youtube/vimeo
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 5. Enrollments (Many-to-Many User <-> Course)
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();
        });

        // 6. Certificate Options
        Schema::create('course_certificate_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // e.g. "Certificado EINSSO"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image_path')->nullable(); // Certificate preview
            $table->timestamps();
        });

        // 7. Campaigns (Discount logic)
        Schema::create('course_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_certificate_option_id')->constrained('course_certificate_options')->cascadeOnDelete();
            $table->string('name'); // e.g. "Pre-venta Navidad"
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('fixed_price', 10, 2)->nullable(); // Override price if set
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_campaigns');
        Schema::dropIfExists('course_certificate_options');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have a teacher (using existing user or creating one)
        // Adjust ID or email based on your existing Users
        $user = User::where('email', 'admin@einso.com')->first() ?? User::first(); 
        
        // Create or find Teacher profile
        $teacher = Teacher::firstOrCreate(
            ['user_id' => $user->id],
            [
                'title' => 'Ingeniero de Sistemas',
                'about' => 'Experto en desarrollo web y sistemas de gestión.',
                'image_path' => null 
            ]
        );

        // 1. Programación Web (ISO)
        $course1 = Course::create([
            'title' => 'AUDITOR INTERNO EN SISTEMAS INTEGRADOS DE GESTIÓN ISO 9001 - ISO 14001 - ISO 45001',
            'slug' => 'auditor-interno-en-sistemas-integrados-de-gestion', // Matches frontend Slug
            'description' => 'Formación clave para auditar sistemas integrados de gestión bajo normas ISO 9001, 14001 y 45001.',
            'category' => 'Gestión Integrada',
            'level' => 'intermediate',
            'status' => 'iniciado',
            'start_date' => '2025-11-25',
            'duration_text' => '4 semanas',
            'is_free' => false,
            'price' => 350.00,
            'teacher_id' => $teacher->id,
            'whatsapp_number' => '+51974496337',
        ]);

        // Modules for Course 1
        $modules1 = [
            'Módulo 01: ISO 19011:2018 - Gestión de Auditorias - I',
            'Módulo 02: ISO 19011:2018 - Gestión de Auditorias - II',
            'Módulo 03: ISO 19011:2018 - Gestión de Auditorias - III',
            'Módulo 04: ISO 9001:2015 - Gestión de Calidad - I',
            'Módulo 05: ISO 9001:2015 - Gestión de Calidad - II',
            'Módulo 06: ISO 14001:2015 - Gestión Ambiental',
            'Módulo 07: ISO 45001:2018 - Gestión de SST - I',
            'Módulo 08: ISO 45001:2018 - Gestión de SST - II',
        ];

        foreach ($modules1 as $index => $title) {
            $mod = CourseModule::create([
                'course_id' => $course1->id,
                'title' => $title,
                'order' => $index + 1,
                'enable_date' => Carbon::parse('2025-11-25')->addDays($index * 2), // Example dates
                'is_published' => true,
            ]);

            // Add dummy lessons
            if ($index == 0) {
                 CourseLesson::create([
                    'module_id' => $mod->id,
                    'title' => 'Video Clase',
                    'type' => 'video',
                    'external_url' => 'https://www.youtube.com/watch?v=PC5i3L4O2Bg',
                    'order' => 1
                ]);
            }
        }

        // 2. SSOMA
        $course2 = Course::create([
            'title' => 'SUPERVISOR SSOMA - Seguridad, Salud Ocupacional y Medio Ambiente',
            'slug' => 'supervisor-ssoma',
            'description' => 'Capacitación esencial para supervisores SSOMA: gestión de seguridad, salud ocupacional y medio ambiente.',
            'category' => 'Seguridad y Salud',
            'level' => 'intermediate',
            'status' => 'finalizado',
            'start_date' => '2025-09-16',
            'duration_text' => '4 semanas',
            'is_free' => true,
            'price' => 0.00,
            'teacher_id' => $teacher->id,
            'whatsapp_number' => '+51974496337',
        ]);

         $modules2 = [
            'Módulo 01: INTRODUCCION SST',
            'Módulo 02: NORMAS LEGALES DE SALUD OCUPACIONAL',
            'Módulo 03: IDENTIFICACIÓN DE RIESGOS Y PELIGROS',
            'Módulo 04: SGST - NORMA ISO 45001:2018',
            'Módulo 05: TRABAJOS DE ALTO RIESGO I',
            'Módulo 06: TRABAJOS DE ALTO RIESGO II',
            'Módulo 07: MATERIALES PELIGROSOS',
            'Módulo 08: GESTION DE RESIDUOS SÓLIDOS',
        ];

        foreach ($modules2 as $index => $title) {
            CourseModule::create([
                'course_id' => $course2->id,
                'title' => $title,
                'order' => $index + 1,
                'is_published' => true,
            ]);
        }
    }
}

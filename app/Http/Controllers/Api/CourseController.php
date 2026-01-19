<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    /**
     * List all active courses (public).
     * filtering by category, status not implemented yet but structure allows it.
     */
    public function index(Request $request)
    {
        $query = Course::with(['teacher.user']) // Removed 'category' relationship as it's a string column now
            ->where('status', 'published');
            
        if ($request->has('category')) {
            $cat = $request->input('category');
            if ($cat && $cat !== 'all') {
                $query->where('category', $cat);
            }
        }

        // Standard filter 'status' from frontend is handled by checking DB status. 
        // NOTE: Frontend logic maps 'paid' / 'free' too. 
        if ($request->has('type')) {
            $type = $request->input('type');
            if ($type === 'free') {
                $query->where('is_free', true);
            } elseif ($type === 'paid') {
                $query->where('is_free', false);
            }
        }

        $courses = $query->orderBy('start_date', 'asc')->get();

        return response()->json($courses->map(function ($course) {
            return $this->transformCourseList($course);
        }));
    }

    /**
     * Get single course details by slug.
     */
    public function show(Request $request, $slug)
    {
        try {
        $course = Course::with([ 
            'teacher.user', 
            'modules.lessons', 
            'certificateOptions.block'
        ])
        ->where('slug', $slug)
        ->firstOrFail();

        // Identify user ID for progress tracking (works for both session and token)
        $userId = null;
        try {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                $userId = $user->id;
            }
        } catch (\Throwable $t) {
            \Illuminate\Support\Facades\Log::error('Error determining user in CourseController: ' . $t->getMessage());
        }

            return response()->json($this->transformCourseDetail($course, $userId));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CourseController show error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error en el servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * enroll currently logged in user (if implementing enrollment via API)
     * For now, frontend handles enrollment via Google Forms. 
     * But we are migrating to "Direct Enrollment".
     */
    public function enroll(Request $request, $slug)
    {
        $user = Auth::user(); // Ensure auth middleware
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();

        // Check if already enrolled
        $existingEnrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Ya estás inscrito en este curso',
                'already_enrolled' => true,
                'data' => [
                    'enrollment' => $existingEnrollment,
                    'course_slug' => $course->slug,
                    'course_id' => $course->id,
                ]
            ]);
        }

        // Create new enrollment
        $enrollment = CourseEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active'
        ]);

        return response()->json([
            'message' => 'Inscripción exitosa',
            'already_enrolled' => false,
            'data' => [
                'enrollment' => $enrollment,
                'course_slug' => $course->slug,
                'course_id' => $course->id,
            ]
        ], 201);
    }

    public function brochure($slug)
    {
        $course = Course::where('slug', $slug)->firstOrFail();
        $path = $course->brochure_path;

        if (!$path) {
            return response()->json(['message' => 'Brochure no disponible'], 404);
        }

        // Limpieza de Path (Manejo de Array/JSON de Filament)
        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
            $decoded = json_decode($path, true);
            $path = is_array($decoded) ? ($decoded[0] ?? array_values($decoded)[0] ?? $path) : $path;
        }
        if (is_array($path)) {
            $path = $path[0] ?? array_values($path)[0] ?? null;
        }

        if (!$path || !is_string($path)) {
             return response()->json(['message' => 'Ruta inválida'], 404);
        }

        // Si es URL completa, extraer path
        if (str_starts_with($path, 'http')) {
             $parsed = parse_url($path);
             $path = ltrim($parsed['path'] ?? '', '/');
        }

        $path = ltrim(trim($path), '/');
        $disk = Storage::disk(config('filesystems.default'));

        // Estrategia de Fuerza Bruta: Probar variantes comunes de encoding que rompen S3/Contabo
        $candidates = array_unique([
            $path,                          // 1. Original (e.g. ...=.pdf)
            urldecode($path),               // 2. Decodificado total
            str_replace('%', '%25', $path), // 3. Doble encoding (raro pero posible)
            str_replace('=', '%3D', $path), // 4. Encode explícito de =
            str_replace('%3D', '=', $path), // 5. Decode explícito de %3D
        ]);

        $finalPath = null;
        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                $finalPath = $candidate;
                break;
            }
        }

        if (!$finalPath) {
            // Último intento: Listar el directorio y buscar coincidencia parcial (lento pero salvador)
            // Solo lo hacemos si el path parece tener la estructura de Filament (carpeta/archivo)
            $dir = dirname($path);
            $base = basename($path);
            
            // Si el dirname es '.' o vacio, evitamos listar root por performance
            if ($dir && $dir !== '.' && $dir !== '/') {
                 try {
                     $files = $disk->files($dir);
                     // Buscamos si alguno de los archivos "limpios" coincide con nuestro base "limpio"
                     // Comparamos ignorando encoding de URL
                     $baseDecoded = urldecode($base);
                     foreach ($files as $f) {
                         if (basename($f) === $base || urldecode(basename($f)) === $baseDecoded) {
                             $finalPath = $f;
                             break;
                         }
                     }
                 } catch (\Throwable $e) {}
            }
        }

        if ($finalPath) {
            // Servir archivo (Proxy)
            try {
                $content = $disk->get($finalPath);
                $mime = 'application/pdf';
                
                // Nombre seguro para descarga
                $safeTitle = preg_replace('/[^A-Za-z0-9_\- ]+/', '', $course->title);
                $name = 'BROCHURE - ' . ($safeTitle ?: 'Curso') . '.pdf';

                return response($content, 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $name . '"',
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            } catch (\Throwable $e) {
                // Log error
            }
        }

        return response()->json(['message' => 'Archivo no encontrado', 'path' => $path], 404);
    }

    // --- Transformers ---

    /**
     * Helper to generate URLs. 
     * Handles Filament's JSON/Array format and generates temporary signed URLs for S3.
     */
   private function generateUrl($path)
{
    if (!$path) return null;

    if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
        $path = json_decode($path, true);
    }

    if (is_array($path)) {
        $path = $path[0] ?? array_values($path)[0] ?? null;
        if (is_array($path)) {
            $path = array_values($path)[0] ?? null;
        }
    }

    if (!$path || !is_string($path)) return null;

    $path = ltrim(trim($path), '/');

    $disk = config('filesystems.default');
    try {
        if ($disk === 's3') {
            $url = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(60));
        } else {
            $url = Storage::disk($disk)->url($path);
        }
    } catch (\Throwable $e) {
        $url = Storage::disk($disk)->url($path);
    }

    // Fix: Decodificar %3D a = para compatibilidad con Contabo/S3 y filenames de Filament que usan Base64
    return str_replace('%3D', '=', $url);
}


    private function transformCourseList(Course $course)
    {
        return [
            'id' => $course->slug, 
            'db_id' => $course->id,
            'slug' => $course->slug,
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'objectives' => $course->objectives,
            'targetAudience' => $course->target_audience,
            'image' => $this->generateUrl($course->image_path),
            'welcomeImage' => $this->generateUrl($course->welcome_image_path),
            'duration' => $course->duration_text,
            'level' => ucfirst($course->level), 
            'status' => $course->status,
            'category' => $course->category,
            'price' => $course->is_free ? 0 : $course->price,
            'instructor' => $course->teacher ? [
                'name' => $course->teacher->user->name ?? 'Instructor',
                'image' => $this->generateUrl($course->teacher->image_path),
                'bio' => $course->teacher->about,
            ] : null,
            'startDate' => $course->start_date ? $course->start_date->format('d/m/Y') : null,
            'endDate' => $course->end_date ? $course->end_date->format('d/m/Y') : null,
            'endDateFull' => $course->end_date ? $course->end_date->translatedFormat('d \d\e F \d\e Y') : null,
            'brochureUrl' => $course->brochure_path ? url('/api/courses/'.$course->slug.'/brochure') : null,
        ];
    }

    private function transformCourseDetail(Course $course, $userId = null)
    {
        $data = $this->transformCourseList($course);
        
        // Detailed fields
        $data['whatsappNumber'] = $course->whatsapp_number;
        $data['sessionsCount'] = $course->sessions_count;
        $data['classType'] = $course->class_type;
        $data['schedule'] = $course->class_schedules ?? [];

        // Get User Progress if logged in
        $completedItems = [];
        if ($userId) {
            // Get enrollment status
            $enrollment = \App\Models\CourseEnrollment::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->first();
            $data['enrollmentStatus'] = $enrollment ? $enrollment->status : null;

            $progress = \App\Models\CourseProgress::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->whereNotNull('module_id')
                ->get();
            
            foreach($progress as $p) {
                if ($p->module_id) {
                    $completedItems[] = 'mod-' . $p->module_id;
                }
            }

            // 2. Evaluation Attempts (Automatic Progress)
            // Logic: Count attempts per evaluation in this course >= 1
            $evalAttempts = \App\Models\EvaluationAttempt::where('user_id', $userId)
                ->whereHas('evaluation', function($q) use ($course) {
                    $q->where('course_id', $course->id);
                })
                ->selectRaw('evaluation_id')
                ->groupBy('evaluation_id')
                ->havingRaw('count(*) >= 1') 
                ->pluck('evaluation_id');
            
            foreach($evalAttempts as $eid) {
                $key = 'eval-' . $eid;
                if (!in_array($key, $completedItems)) {
                    $completedItems[] = $key;
                }
            }
        }
        $data['userProgress'] = $completedItems;
        
        // Unified Syllabus (Modules + Evaluations)
        $modules = $course->modules->map(function ($mod) {
            $mod->content_type = 'module';
            return $mod;
        });

        $evaluations = \App\Models\Evaluation::where('course_id', $course->id)->get()->map(function ($eval) use ($userId) {
            $eval->content_type = 'evaluation';
            
            $maxAttemptNumber = $userId ? \App\Models\EvaluationAttempt::where('user_id', $userId)
                                                    ->where('evaluation_id', $eval->id)
                                                    ->max('attempt_number') : null;
            
            $eval->user_attempts_count = $maxAttemptNumber ?? 0;
            return $eval;
        });

        $unified = $modules->concat($evaluations)->sortBy('order')->values();

        $data['modules'] = $unified->map(function ($item) use ($userId) {
            if ($item->content_type === 'module') {
                return [
                    'type' => 'module',
                    'id' => 'mod-' . $item->id,
                    'title' => $item->title,
                    'order' => $item->order,
                    'enableDate' => $item->enable_date ? $item->enable_date->format('d/m/Y h:i a') : null,
                    'lessons' => $item->lessons->map(function ($lesson) {
                        return [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'type' => 'text',
                        ];
                    }),
                    'topics' => $item->lessons->pluck('title')->toArray(),
                    'videoUrl' => $item->video_url,
                    'pdfUrl' => $item->pdf_path ? $this->generateUrl($item->pdf_path) : 
                                (($p = $item->lessons->where('type', 'pdf')->first()) ? $this->generateUrl($p->content_url) : null),
                    'zoomUrl' => $item->zoom_url,
                    'classTime' => $item->class_time, // Formato "H:i:s" o el que venga DB
                ];
            } else {
                $evalAttemptsTotal = $item->attempts;
                if ($userId && $item->attempts > 0) {
                     $extension = \App\Models\EvaluationUserExtension::where('user_id', $userId)
                        ->where('evaluation_id', $item->id)
                        ->first();
                     if ($extension) {
                         $evalAttemptsTotal += $extension->extra_attempts;
                     }
                }

                return [
                    'type' => 'evaluation',
                    'id' => 'eval-' . $item->id,
                    'title' => $item->title,
                    'order' => $item->order,
                    'startDate' => $item->start_date ? $item->start_date->toIso8601String() : null,
                    'endDate' => $item->end_date ? $item->end_date->toIso8601String() : null,
                    'attempts' => $evalAttemptsTotal, // Dynamically adjusted limit
                    'userAttempts' => $item->user_attempts_count,
                    'url' => '#',
                ];
            }
        });

        // Certificate Options
        $data['certificateOptions'] = $course->certificateOptions->sortBy('created_at')->map(function ($opt) {
            $blockData = [];
            // Include certification block information if assigned
            if ($opt->certification_block_id && $opt->block) {
                $blockData = [
                    'blockName' => $opt->block->name,
                    'blockStartDate' => $opt->block->start_date->format('d/m/Y'),
                    'blockEndDate' => $opt->block->end_date->format('d/m/Y'),
                    'blockIsActive' => $opt->block->is_active,
                ];
            }

            return array_merge([
                'id' => $opt->id,
                'type' => $opt->type,
                'title' => $opt->title,
                'description' => $opt->description,
                'details' => $opt->details,
                'price' => $opt->price,
                'image1Url' => $this->generateUrl($opt->image_1_path),
                'image2Url' => $this->generateUrl($opt->image_2_path),
                'imageUrl' => $this->generateUrl($opt->image_1_path), 
                'academicHours' => $opt->academic_hours,
                'megapackItems' => $opt->megapack_items,
                'discountPercentage' => $opt->discount_percentage,
                'discountEndDate' => $opt->discount_end_date ? $opt->discount_end_date->format('d/m/Y') : null,
            ], $blockData);
        })->values();

        return $data;
    }

    public function toggleProgress(Request $request, $slug)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $course = \App\Models\Course::where('slug', $slug)->firstOrFail();
        $moduleId = $request->input('module_id');

        if (!$moduleId) {
            return response()->json(['message' => 'Missing module_id'], 400);
        }

        // Verificar que el módulo existe y pertenece al curso
        $module = \App\Models\CourseModule::where('course_id', $course->id)
                    ->where('id', $moduleId)
                    ->first();

        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        // Marcar como completado si no existe
        \App\Models\CourseProgress::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
        ], [
            'completed_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'completed' => true]);
    }
    public function myCourses(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        if (!$user) {
            return response()->json([], 401);
        }

        // Get ALL enrollments (including inactive) so frontend can handle the UI
        $enrollments = \App\Models\CourseEnrollment::where('user_id', $user->id)
                        ->with(['course.teacher.user', 'course.modules.lessons']) 
                        ->get();
        
        $courses = $enrollments->map(function ($enrollment) use ($user) {
             if (!$enrollment->course) return null;
             return $this->transformMyCourseList($enrollment->course, $user->id, $enrollment->status);
        })->filter(function($c) { return $c !== null; })->values();

        return response()->json($courses);
    }

    private function transformMyCourseList(\App\Models\Course $course, $userId, $enrollmentStatus = 'active')
    {
        $data = $this->transformCourseList($course);
        
        // Add enrollment status so frontend can handle inactive users
        $data['enrollmentStatus'] = $enrollmentStatus;
        
        // 1. Estadísticas de Módulos
        // Total de módulos del curso
        $totalModules = $course->modules->count();
        
        // Módulos completados por el usuario (basado en la tabla course_progress con module_id)
        $completedModules = \App\Models\CourseProgress::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->whereNotNull('module_id')
            ->distinct('module_id')
            ->count('module_id');

        // 2. Estadísticas de Evaluaciones
        $totalEvaluations = \App\Models\Evaluation::where('course_id', $course->id)->count();
        
        $completedEvaluations = 0;
        if ($totalEvaluations > 0) {
            $completedEvaluations = \App\Models\EvaluationAttempt::where('user_id', $userId)
                ->whereHas('evaluation', function($q) use ($course) {
                    $q->where('course_id', $course->id);
                })
                ->distinct('evaluation_id')
                ->count('evaluation_id');
        }

        // 3. Cálculo de Progreso General
        // El 100% es la suma de (Todos los Módulos + Todas las Evaluaciones)
        // El progreso del usuario es (Módulos Completados + Evaluaciones Completadas)
        
        $totalItems = $totalModules + $totalEvaluations;
        $completedItems = $completedModules + $completedEvaluations;
        
        $progress = 0;
        if ($totalItems > 0) {
            $progress = ($completedItems / $totalItems) * 100;
        }

        $data['completedModules'] = $completedModules;
        $data['totalModules'] = $totalModules;
        $data['completedEvaluations'] = $completedEvaluations;
        $data['totalEvaluations'] = $totalEvaluations;
        // Nos aseguramos que no pase de 100 por si acaso
        $data['progress'] = min(round($progress), 100);

        return $data;
    }
}

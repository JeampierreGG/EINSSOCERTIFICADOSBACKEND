<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('user')->orderBy('created_at', 'desc')->get();

        $teachers->each(function ($teacher) {
            if ($teacher->image_path) {
                $teacher->image_url = url('/api/teachers/' . $teacher->id . '/image') . '?v=' . $teacher->updated_at->timestamp;
            }
        });

        return response()->json($teachers);
    }

    public function show($id)
    {
        $teacher = Teacher::with(['user', 'courses' => function($q) {
            $q->where('status', 'published');
        }])->find($id);

        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        if ($teacher->image_path) {
            $teacher->image_url = url('/api/teachers/' . $teacher->id . '/image') . '?v=' . $teacher->updated_at->timestamp;
        }

        $teacherArray = $teacher->toArray();
        $teacherArray['courses'] = $teacher->courses->map(function ($course) use ($teacher) {
            return $this->transformCourse($course, $teacher);
        });

        return response()->json($teacherArray);
    }

    private function transformCourse($course, $teacher)
    {
        return [
            'id' => $course->slug,
            'db_id' => $course->id,
            'slug' => $course->slug,
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'image' => $this->generateUrl($course->image_path),
            'duration' => $course->duration_text,
            'level' => ucfirst($course->level),
            'status' => $course->status,
            'category' => $course->category,
            'price' => $course->is_free ? 0 : $course->price,
            'instructor' => [
                'name' => $teacher->user->name ?? 'Instructor',
                'image' => $teacher->image_url,
            ],
            'startDate' => $course->start_date ? $course->start_date->format('d/m/Y') : null,
            'endDate' => $course->end_date ? $course->end_date->format('d/m/Y') : null,
        ];
    }

    private function generateUrl($path)
    {
        if (!$path) return null;

        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
            $path = json_decode($path, true);
        }

        if (is_array($path)) {
            $path = $path[0] ?? array_values($path)[0] ?? null;
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

        return str_replace('%3D', '=', $url);
    }

    public function image(Teacher $teacher)
    {
        $path = $teacher->image_path;
        if (!$path || !Storage::disk(config('filesystems.default'))->exists($path)) {
            return response()->noContent(404);
        }

        $disk = Storage::disk(config('filesystems.default'));
        $content = $disk->get($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];
        $mime = $mimeMap[$extension] ?? 'application/octet-stream';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

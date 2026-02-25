<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\CourseReminderImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CourseOpening extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public CourseEnrollment $enrollment;
    public ?CourseModule    $firstModule;
    public ?string          $reminderImageUrl;
    public ?string          $courseUrl;

    public function __construct(CourseEnrollment $enrollment)
    {
        $this->enrollment = $enrollment;

        // Primer módulo del curso (menor orden)
        $this->firstModule = CourseModule::where('course_id', $enrollment->course_id)
            ->orderBy('order')
            ->first();

        $this->reminderImageUrl = $this->resolveReminderImageUrl($enrollment->course_id);
        $this->courseUrl        = $this->buildCourseUrl($enrollment);
    }

    public function envelope(): Envelope
    {
        $courseTitle = $this->enrollment->course?->title ?? 'el curso';
        return new Envelope(
            subject: '🚀 ¡Hoy es el día! Primera Sesión en Vivo — ' . $courseTitle . ' | EINSSO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_opening',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    // -----------------------------------------------------------------------

    private function resolveReminderImageUrl(int $courseId): ?string
    {
        $reminder = CourseReminderImage::where('course_id', $courseId)
            ->where('type', 'opening')
            ->whereNull('evaluation_id')
            ->first();

        if (!$reminder || !$reminder->image_path) return null;

        $path = $this->normalizePath($reminder->image_path);
        if (!$path) return null;

        try {
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addHours(48));
            return str_replace('%3D', '=', $url);
        } catch (\Throwable) {
            try {
                return str_replace('%3D', '=', Storage::disk('s3')->url($path));
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function buildCourseUrl(CourseEnrollment $enrollment): string
    {
        $slug = $enrollment->course?->slug ?? '';
        // Si no está logueado, redirigir a login con intención de ir al curso
        return 'https://einssoconsultores.com/auth?redirect=' . urlencode('/curso/' . $slug);
    }

    private function normalizePath(mixed $path): ?string
    {
        if (is_string($path) && str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            $path = is_array($decoded) ? (array_values($decoded)[0] ?? null) : $path;
        }
        if (is_array($path)) {
            $path = array_values($path)[0] ?? null;
        }
        if (!$path || !is_string($path)) return null;
        return ltrim(trim($path), '/');
    }
}

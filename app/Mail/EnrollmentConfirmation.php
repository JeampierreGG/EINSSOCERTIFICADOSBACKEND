<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use App\Models\CourseReminderImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class EnrollmentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public CourseEnrollment $enrollment;
    public ?string $reminderImageUrl;

    public function __construct(CourseEnrollment $enrollment)
    {
        $this->enrollment = $enrollment;
        $this->reminderImageUrl = $this->resolveReminderImageUrl($enrollment->course_id);
    }

    public function envelope(): Envelope
    {
        $courseTitle = $this->enrollment->course?->title ?? 'el curso';
        return new Envelope(
            subject: '✅ Confirmación de Matrícula — ' . $courseTitle . ' | EINSSO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enrollment_confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Obtiene la URL pública/temporal de la imagen de recordatorio de matrícula.
     */
    private function resolveReminderImageUrl(int $courseId): ?string
    {
        $reminder = CourseReminderImage::where('course_id', $courseId)
            ->where('type', 'enrollment')
            ->whereNull('evaluation_id')
            ->first();

        if (!$reminder || !$reminder->image_path) {
            return null;
        }

        $path = $reminder->image_path;

        // Normalizar si viene como array/JSON (edge case de Filament)
        if (is_string($path) && str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            $path = is_array($decoded) ? (array_values($decoded)[0] ?? null) : $path;
        }
        if (is_array($path)) {
            $path = array_values($path)[0] ?? null;
        }
        if (!$path || !is_string($path)) {
            return null;
        }

        $path = ltrim(trim($path), '/');

        try {
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addHours(48));
            return str_replace('%3D', '=', $url);
        } catch (\Throwable $e) {
            try {
                return str_replace('%3D', '=', Storage::disk('s3')->url($path));
            } catch (\Throwable) {
                return null;
            }
        }
    }
}

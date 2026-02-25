<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use App\Models\CourseReminderImage;
use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class EvaluationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public CourseEnrollment $enrollment;
    public Evaluation       $evaluation;
    public ?string          $reminderImageUrl;
    public ?string          $courseUrl;

    public function __construct(CourseEnrollment $enrollment, Evaluation $evaluation)
    {
        $this->enrollment       = $enrollment;
        $this->evaluation       = $evaluation;
        $this->reminderImageUrl = $this->resolveReminderImageUrl($enrollment->course_id, $evaluation->id);
        $this->courseUrl        = 'https://einssoconsultores.com/auth?redirect='
            . urlencode('/curso/' . ($enrollment->course?->slug ?? ''));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📝 Evaluación disponible: ' . $this->evaluation->title . ' | EINSSO',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.evaluation_notification');
    }

    public function attachments(): array
    {
        return [];
    }

    private function resolveReminderImageUrl(int $courseId, int $evaluationId): ?string
    {
        $reminder = CourseReminderImage::where('course_id', $courseId)
            ->where('type', 'evaluation')
            ->where('evaluation_id', $evaluationId)
            ->first();

        return $this->buildS3Url($reminder?->image_path);
    }

    private function buildS3Url(mixed $path): ?string
    {
        if (!$path) return null;
        if (is_string($path) && str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            $path = is_array($decoded) ? (array_values($decoded)[0] ?? null) : $path;
        }
        if (is_array($path)) { $path = array_values($path)[0] ?? null; }
        if (!$path || !is_string($path)) return null;
        $path = ltrim(trim($path), '/');
        try {
            return str_replace('%3D', '=', Storage::disk('s3')->temporaryUrl($path, now()->addHours(48)));
        } catch (\Throwable) {
            try { return str_replace('%3D', '=', Storage::disk('s3')->url($path)); }
            catch (\Throwable) { return null; }
        }
    }
}

<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $certificate;

    /**
     * Create a new message instance.
     */
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Entrega de Certificado - EINSSO',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate_sent',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Check for main file path (Solo Certificate)
        if ($this->certificate->file_path) {
            $disk = config('filesystems.default');
            if (Storage::disk($disk)->exists($this->certificate->file_path)) {
                // If using S3, we might need a temporary URL or stream.
                // However, Mailable::fromStorage handles disks configured in Laravel.
                $attachments[] = Attachment::fromStorageDisk($disk, $this->certificate->file_path)
                    ->as('Certificado-' . $this->certificate->code . '.' . pathinfo($this->certificate->file_path, PATHINFO_EXTENSION))
                    ->withMime(Storage::disk($disk)->mimeType($this->certificate->file_path));
            }
        }

        // Check for items (Megapack)
        foreach ($this->certificate->items as $item) {
            if ($item->file_path) {
                $disk = config('filesystems.default');
                if (Storage::disk($disk)->exists($item->file_path)) {
                     $attachments[] = Attachment::fromStorageDisk($disk, $item->file_path)
                        ->as('Certificado-' . $item->code . '.' . pathinfo($item->file_path, PATHINFO_EXTENSION))
                        ->withMime(Storage::disk($disk)->mimeType($item->file_path));
                }
            }
        }

        return $attachments;
    }
}

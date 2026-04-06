<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FreePackageReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public int $daysLeft
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Rekonek] Paket Free Anda tersisa {$this->daysLeft} hari",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.free-package-reminder',
            with: [
                'contactName' => $this->contactName,
                'daysLeft' => $this->daysLeft,
            ],
        );
    }
}

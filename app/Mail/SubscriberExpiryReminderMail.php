<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriberExpiryReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $expiredDateLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Rekonek] Pengingat: langganan Anda akan berakhir (H-7)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscriber-expiry-reminder',
            with: [
                'contactName' => $this->contactName,
                'expiredDateLabel' => $this->expiredDateLabel,
            ],
        );
    }
}


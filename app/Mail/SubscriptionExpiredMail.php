<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $packageName,
        public string $expiredDateLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Rekonek] Masa aktif langganan Anda telah berakhir',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expired',
            with: [
                'contactName' => $this->contactName,
                'packageName' => $this->packageName,
                'expiredDateLabel' => $this->expiredDateLabel,
            ],
        );
    }
}

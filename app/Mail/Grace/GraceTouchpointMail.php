<?php

namespace App\Mail\Grace;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * GraceTouchpointMail
 *
 * Mailable dinamis untuk seluruh touchpoint grace period. View template
 * dan subject ditentukan oleh config('grace-touchpoints') per touchpoint,
 * diteruskan dari GraceDripService → SendGraceEmailJob → mailable ini.
 *
 * Satu kelas dipakai untuk 11 email template berbeda, sehingga tidak perlu
 * bikin Mailable terpisah per touchpoint.
 */
class GraceTouchpointMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $viewName  Blade view, e.g. 'emails.grace.h-plus-1'
     * @param  string  $subjectLine  Subject email
     * @param  array   $payload  Data yang diteruskan ke view
     */
    public function __construct(
        public string $viewName,
        public string $subjectLine,
        public array $payload
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: $this->payload,
        );
    }
}

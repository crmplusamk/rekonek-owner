<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ThankYouSubscribedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $invoiceCode,
        public string $paymentDateLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Rekonek] Selamat bergabung — terima kasih telah berlangganan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.thankyou-subscription',
            with: [
                'contactName' => $this->contactName,
                'invoiceCode' => $this->invoiceCode,
                'paymentDateLabel' => $this->paymentDateLabel,
            ],
        );
    }
}


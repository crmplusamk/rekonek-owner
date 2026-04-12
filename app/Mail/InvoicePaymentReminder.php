<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $invoiceCode,
        public string $invoiceTotal,
        public string $expiredDate,
        public string $dueDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Rekonek] Invoice perpanjangan langganan — segera lakukan pembayaran',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-payment-reminder',
            with: [
                'contactName' => $this->contactName,
                'invoiceCode' => $this->invoiceCode,
                'invoiceTotal' => $this->invoiceTotal,
                'expiredDate' => $this->expiredDate,
                'dueDate' => $this->dueDate,
            ],
        );
    }
}

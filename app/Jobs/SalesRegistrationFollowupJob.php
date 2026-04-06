<?php

namespace App\Jobs;

use App\Helpers\Whatsapp\WhatsappHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Contact\App\Models\Contact;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class SalesRegistrationFollowupJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SALES_NOTIFICATION_PHONE = '6282121938547';

    public function __construct(
        public string $contactId
    ) {}

    // public function uniqueId(): string
    // {
    //     return 'sales_registration_followup_'.$this->contactId;
    // }

    // public function uniqueFor(): int
    // {
    //     return 60 * 60 * 48;
    // }

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);

        if (! $contact) {
            Log::warning('SalesRegistrationFollowupJob: contact not found', ['contact_id' => $this->contactId]);

            return;
        }

        if ($contact->sales_registration_followup_sent_at) {
            return;
        }

        $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

        if (! $session) {
            Log::warning('SalesRegistrationFollowupJob: no active WhatsApp session');

            return;
        }

        $name = $contact->name ?? '—';
        $email = $contact->email ?? '—';
        $phone = $contact->phone ?? '—';
        $verifiedAt = optional($contact->email_verified_at)->format('d M Y, H:i') ?? '—';

        $message = "📌 *Follow-up registrasi (H+1)*\n\n".
            "Pengingat: lead berikut sudah verifikasi email ±24 jam lalu.\n\n".
            "*Nama:* {$name}\n".
            "*Email:* {$email}\n".
            "*No. WA:* {$phone}\n".
            "*Email diverifikasi:* {$verifiedAt}\n\n".
            '_Notifikasi otomatis dari Rekonek_';

        $result = WhatsappHelper::sendTextMessage(
            $session->session,
            self::SALES_NOTIFICATION_PHONE,
            $message
        );

        if ($result === false) {
            Log::error('SalesRegistrationFollowupJob: sendTextMessage failed', [
                'contact_id' => $this->contactId,
            ]);

            throw new \RuntimeException('WhatsApp follow-up send failed');
        }

        $contact->update([
            'sales_registration_followup_sent_at' => now(),
        ]);
    }
}

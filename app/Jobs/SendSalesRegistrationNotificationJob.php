<?php

namespace App\Jobs;

use App\Helpers\Whatsapp\WhatsappHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Contact\App\Models\Contact;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class SendSalesRegistrationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private function salesNotificationPhone(): string
    {
        $v = trim((string) env('SALES_PHONE', ''));

        return $v !== '' ? $v : '6285150741783';
    }

    public function __construct(
        public string $contactId
    ) {}

    public function handle(): void
    {
        try {

            $contact = Contact::find($this->contactId);

            if (! $contact) {
                Log::warning('SendSalesRegistrationNotificationJob: contact not found', [
                    'contact_id' => $this->contactId,
                ]);

                return;
            }

            $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

            if (! $session) {
                Log::warning('SendSalesRegistrationNotificationJob: no active WhatsApp session');

                return;
            }

            $name = $contact->name ?? '—';
            $email = $contact->email ?? '—';
            $phone = $contact->phone ?? '—';

            $registeredAt = $contact->created_at
                ? Carbon::parse($contact->created_at)
                    ->timezone(config('app.timezone'))
                    ->locale('id')
                    ->translatedFormat('d F Y, H:i')
                : '—';

            $message = "📢 *Pendaftaran baru*\n\n".
                "*Nama:* {$name}\n".
                "*Email:* {$email}\n".
                "*No. WA:* {$phone}\n".
                "*Tanggal & jam daftar:* {$registeredAt}\n\n".
                "Mohon hubungi lead ini untuk konfirmasi kebutuhan, bantu onboarding, dan pastikan mereka memahami langkah selanjutnya setelah verifikasi email.\n\n".
                '_Notifikasi otomatis dari Rekonek_';

            $result = WhatsappHelper::sendTextMessage(
                $session->session,
                $this->salesNotificationPhone(),
                $message
            );

            if ($result === false) {
                Log::error('SendSalesRegistrationNotificationJob: sendTextMessage returned false', [
                    'contact_id' => $this->contactId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendSalesRegistrationNotificationJob: '.$e->getMessage(), [
                'contact_id' => $this->contactId,
            ]);
        }
    }
}

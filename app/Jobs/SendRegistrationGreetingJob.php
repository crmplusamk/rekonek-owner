<?php

namespace App\Jobs;

use App\Helpers\Whatsapp\WhatsappHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class SendRegistrationGreetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $phone,
        public string $customerName
    ) {}

    public function handle(): void
    {
        if (empty($this->phone)) {
            Log::warning('SendRegistrationGreetingJob: empty phone, skip');

            return;
        }

        try {

            $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

            if (! $session) {
                Log::warning('SendRegistrationGreetingJob: no active WhatsApp session');

                return;
            }

            $message = "Halo Kak *{$this->customerName}*! 👋\n\n".
                "Terima kasih sudah memilih Rekonek untuk jadi pusat komando bisnis Anda. Akun Anda sudah siap!\n\n".
                "Agar tidak bingung, yuk tonton video panduan setup 2 menit ini:\n".
                "https://www.youtube.com/watch?v=u063lZ-zDGQ\n\n".
                "*Langkah pertama Anda:*\n\n".
                "1. Login ke Dashboard:\n".
                "https://app.rekonek.com/login\n\n".
                "2. Hubungkan WhatsApp (Scan QR)\n\n".
                "3. Atur akses tim Anda.\n\n".
                "Selamat tinggal blindspot 🚀\n\n".
                '_Ini adalah pesan otomatis dari Rekonek_';

            $result = WhatsappHelper::sendTextMessage(
                $session->session,
                $this->phone,
                $message
            );

            if ($result === false) {
                Log::error('SendRegistrationGreetingJob: sendTextMessage returned false', [
                    'phone' => $this->phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendRegistrationGreetingJob: '.$e->getMessage(), [
                'phone' => $this->phone,
            ]);
        }
    }
}

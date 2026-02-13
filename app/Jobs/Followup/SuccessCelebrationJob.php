<?php

namespace App\Jobs\Followup;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Models\AccessLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Customer\App\Models\Customer;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class SuccessCelebrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected AccessLog $accessLog;

    public function __construct(AccessLog $accessLog)
    {
        $this->accessLog = $accessLog;
    }

    public function handle(): void
    {
        $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

        if (! $session) {
            Log::info('No active WhatsappOtp session for celebration message');

            return;
        }

        $customer = Customer::where('company_id', $this->accessLog->company_id)->first();

        // Fallback info if customer not found (should exist though)
        $phone = $this->accessLog->number ?? ($customer ? $customer->phone : null);
        $name = $customer ? $customer->name : ($this->accessLog->email ?? 'Kakak');

        if (empty($phone)) {
            Log::warn("No phone number found for celebration message. Company ID: {$this->accessLog->company_id}");

            return;
        }

        $message = $this->getCelebrationMessage($name);

        try {
            $response = WhatsappHelper::sendTextMessage($session->session, $phone, $message);

            if ($response !== false) {
                $this->markCelebrationSent();
                Log::info("Celebration message sent to company_id={$this->accessLog->company_id} ({$phone})");
            } else {
                Log::error("Failed to send WA celebration to company_id={$this->accessLog->company_id}");
            }
        } catch (\Exception $e) {
            Log::error('Error sending celebration message: '.$e->getMessage());
        }
    }

    protected function getCelebrationMessage(string $name): string
    {
        return "Yess! WhatsApp Anda sudah Berhasil Terhubung ke Rekonek. 🎉\n\n".
            "Saat ini sistem sedang menarik database kontak Kakak di latar belakang. Sambil menunggu, Kakak bisa mulai:\n\n".
            "✅ Menghapus Data Dummy (Data simulasi) di Dashboard.\n".
            "✅ Memberi akses ke Admin Sales Kakak.\n".
            "✅ Mengimpor kontak ke Rekonek\n\n".
            "Dashboard sudah mulai mencatat statistik real-time tim Kakak sekarang. Cek di sini: https://app.rekonek.com/login\n\n".
            "_Ini adalah pesan otomatis dari Rekonek_";
    }

    protected function markCelebrationSent(): void
    {
        AccessLog::create([
            'category' => 'celebration',
            'company_id' => $this->accessLog->company_id,
            'email' => $this->accessLog->email,
            'number' => $this->accessLog->number,
            'action' => 'send_celebration_message',
            'activity_type' => 'whatsapp_message',
            'progress' => 'celebration_sent',
        ]);
    }
}

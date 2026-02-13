<?php

namespace App\Jobs\Followup;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Models\AccessLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Customer\App\Models\Customer;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class IncompleteSetupReminderJob implements ShouldQueue
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
            Log::info('No active WhatsappOtp session for incomplete setup reminder');

            return;
        }

        if ($this->alreadyWhappConnected()) {
            Log::info('Customer already connected whatsapp, skipping reminder: '.($this->accessLog->company_id ?? $this->accessLog->email));

            return;
        }

        $customer = Customer::where('company_id', $this->accessLog->company_id)->first();

        if (! $customer) {
            Log::warn("Customer not found for company_id: {$this->accessLog->company_id}");

            return;
        }

        $phone = $this->accessLog->number ?? $customer->phone;

        if (empty($phone)) {
            Log::warn("No phone number for company_id: {$this->accessLog->company_id}");

            return;
        }

        $name = $customer->name ?? 'Sobat Rekonek';
        $message = $this->getReminderMessage($name);

        try {
            $response = WhatsappHelper::sendTextMessage($session->session, $phone, $message);

            if ($response !== false) {
                $this->markReminderSent();
                Log::info("Incomplete-setup reminder sent to company_id={$this->accessLog->company_id} ({$phone})");
            } else {
                Log::error("Failed to send WA reminder for company_id={$this->accessLog->company_id}");
            }
        } catch (\Exception $e) {
            Log::error('Error sending incomplete-setup reminder: '.$e->getMessage());
        }
    }

    protected function alreadyWhappConnected(): bool
    {
        try {
            $companyIds = [$this->accessLog->company_id];

            $alreadyConnected = DB::connection('client')
                ->table('chat_sessions')
                ->whereIn('company_id', $companyIds)
                ->where('status', 1)
                ->exists();

            if ($alreadyConnected) {
                return true;
            }

            $logConnected = DB::table('access_logs')
                ->where('progress', AccessLog::PROGRESS_WA_CONNECTED)
                ->where('company_id', $this->accessLog->company_id)
                ->exists();

            return $logConnected;
        } catch (\Exception $e) {
            Log::error('Failed to check chat_sessions: '.$e->getMessage());

            return false;
        }
    }

    protected function getReminderMessage(string $name): string
    {
        return "Halo *{$name}*! 👋\n\n".
            'Kami perhatikan Anda sudah mengaktifkan trial namun belum menghubungkan WhatsApp. '.
            "Yuk segera sambungkan agar Anda bisa memaksimalkan fitur Rekonek.\n\n".
            "*Langkah singkat:*\n\n".
            "1. Login ke Dashboard:\nhttps://app.rekonek.com/login\n\n".
            "2. Hubungkan WhatsApp (Scan QR)\n\n".
            "3. Atur akses tim Anda.\n\n".
            "Panduan 2 menit: https://www.youtube.com/watch?v=u063lZ-zDGQ\n\n".
            '_Ini adalah pesan otomatis dari Rekonek_';
    }

    protected function markReminderSent(): void
    {
        AccessLog::create([
            'category' => 'reminder',
            'company_id' => $this->accessLog->company_id,
            'email' => $this->accessLog->email ?? null,
            'number' => $this->accessLog->number ?? null,
            'action' => 'incomplete_setup_reminder',
            'activity_type' => 'whatsapp_message',
            'progress' => AccessLog::PROGRESS_INCOMPLETE_SETUP_REMINDER_SENT,
        ]);
    }
}

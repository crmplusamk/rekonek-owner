<?php

namespace App\Console\Commands;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Models\AccessLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customer\App\Models\Customer;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class IncompleteSetupReminderCommand extends Command
{
    protected $signature = 'reminder:incomplete-setup';

    protected $description = 'Kirim reminder WA ke customer yang sudah trial_activated ≥30 menit tapi belum whatsapp_connected (hanya sekali, tandai dengan incomplete_setup_reminder_sent)';

    public function handle()
    {
        $this->info('Checking for customers needing incomplete-setup reminder...');

        // Poin 1: Cek sesi WA aktif lebih awal, sebelum query apapun
        $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

        if (!$session) {
            $this->warn('No active WhatsappOtp session; skipping send.');
            return Command::SUCCESS;
        }

        $cutoffTime = Carbon::now()->subMinutes(30);

        // 1) Company_id yang sudah whatsapp_connected ATAU sudah dapat reminder
        $skipCompanyIds = DB::table('access_logs')
            ->whereIn('progress', [
                AccessLog::PROGRESS_WA_CONNECTED,
                AccessLog::PROGRESS_INCOMPLETE_SETUP_REMINDER_SENT,
            ])
            ->whereNotNull('company_id')
            ->distinct()
            ->pluck('company_id')
            ->flip()
            ->all();

        // 2) Log trial_activated yang sudah ≥30 menit dan punya company_id
        $logsAtTrial = DB::table('access_logs')
            ->where('progress', AccessLog::PROGRESS_TRIAL_ACTIVATED)
            ->whereNotNull('company_id')
            ->where('created_at', '<=', $cutoffTime)
            ->orderBy('created_at')
            ->get();

        $seen = [];
        $toRemind = [];

        foreach ($logsAtTrial as $log) {
            if (isset($seen[$log->company_id])) {
                continue;
            }
            if (isset($skipCompanyIds[$log->company_id])) {
                $seen[$log->company_id] = true;
                continue;
            }
            $seen[$log->company_id] = true;
            $toRemind[] = $log;
        }

        if (empty($toRemind)) {
            $this->info('No customers eligible for reminder.');
            return Command::SUCCESS;
        }

        // 3) Ambil list company_id dari kandidat
        $companyIds = collect($toRemind)->pluck('company_id')->unique()->values()->all();

        // 4) Cek real-time ke tabel chat_sessions (Database Client)
        // Poin 6: Jika koneksi gagal, hentikan seluruh proses (fail-safe)
        try {
            $alreadyConnectedCompanyIds = DB::connection('client')
                ->table('chat_sessions')
                ->whereIn('company_id', $companyIds)
                ->pluck('company_id')
                ->flip()
                ->all();
        } catch (\Exception $e) {
            $this->error("Failed to check chat_sessions: " . $e->getMessage());
            return Command::FAILURE;
        }

        if (!empty($alreadyConnectedCompanyIds)) {
            $filteredRemind = [];
            foreach ($toRemind as $log) {
                if (isset($alreadyConnectedCompanyIds[$log->company_id])) {
                    $this->info("Skipping company_id={$log->company_id}; found active chat_session.");
                    continue;
                }
                $filteredRemind[] = $log;
            }
            $toRemind = $filteredRemind;

            // Update $companyIds agar query Customer efisien
            $companyIds = array_diff($companyIds, array_keys($alreadyConnectedCompanyIds));
        }

        if (empty($toRemind)) {
            $this->info('No customers eligible for reminder after chat_session check.');
            return Command::SUCCESS;
        }

        // 5) Load Customer data hanya untuk yang valid
        $customersByCompany = Customer::whereIn('company_id', $companyIds)->get()->keyBy('company_id');

        $sent = 0;

        foreach ($toRemind as $log) {
            $phone = $this->getPhoneForLog($log, $customersByCompany);
            if (empty($phone)) {
                $this->warn("No phone for company_id={$log->company_id}; skip.");
                continue;
            }

            $name = $this->getNameForLog($log, $customersByCompany);
            $message = $this->getReminderMessage($name);

            try {
                $response = WhatsappHelper::sendTextMessage($session->session, $phone, $message);
                if ($response !== false) {
                    $this->markReminderSent($log);
                    $sent++;
                    $this->info("Reminder sent to company_id={$log->company_id} ({$phone}).");
                } else {
                    $this->warn("Send failed for company_id={$log->company_id}.");
                }
            } catch (\Exception $e) {
                $this->error("Error sending to company_id={$log->company_id}: " . $e->getMessage());
            }
        }

        $this->info("Incomplete-setup reminder: {$sent} sent, " . count($toRemind) . " eligible.");
        return Command::SUCCESS;
    }

    protected function getPhoneForLog(object $log, Collection $customersByCompany): ?string
    {
        if (!empty($log->number)) {
            return $log->number;
        }
        $c = $customersByCompany->get($log->company_id);
        return $c->phone ?? null;
    }

    protected function getNameForLog(object $log, Collection $customersByCompany): string
    {
        $c = $customersByCompany->get($log->company_id);
        return ($c && !empty($c->name)) ? $c->name : 'Sobat Rekonek';
    }

    protected function getReminderMessage(string $name): string
    {
        return "Halo *{$name}*! 👋\n\n" .
            "Kami perhatikan Anda sudah mengaktifkan trial namun belum menghubungkan WhatsApp. " .
            "Yuk segera sambungkan agar Anda bisa memaksimalkan fitur Rekonek.\n\n" .
            "*Langkah singkat:*\n\n" .
            "1. Login ke Dashboard:\nhttps://app.rekonek.com/login\n\n" .
            "2. Hubungkan WhatsApp (Scan QR)\n\n" .
            "3. Atur akses tim Anda.\n\n" .
            "Panduan 2 menit: https://www.youtube.com/watch?v=u063lZ-zDGQ\n\n" .
            "_Ini adalah pesan otomatis dari Rekonek_";
    }

    protected function markReminderSent(object $log): void
    {
        if (empty($log->company_id)) {
            return;
        }

        AccessLog::create([
            'category' => 'reminder',
            'company_id' => $log->company_id,
            'email' => $log->email ?? null,
            'number' => $log->number ?? null,
            'action' => 'incomplete_setup_reminder',
            'activity_type' => 'whatsapp_message',
            'progress' => AccessLog::PROGRESS_INCOMPLETE_SETUP_REMINDER_SENT,
        ]);
    }
}
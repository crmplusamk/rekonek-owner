<?php

namespace App\Jobs\Grace;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Models\GraceLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

/**
 * Job mengirim WhatsApp message untuk satu touchpoint grace period.
 * Menggunakan session aktif dari WhatsappOtpSession (sama seperti SendSalesRegistrationNotificationJob).
 *
 * Update status grace_logs:
 *   - 'sent'   jika WhatsappHelper mengembalikan error=false
 *   - 'failed' jika session tidak aktif, atau API mengembalikan error=true, atau exception
 */
class SendGraceWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    /**
     * @param  string|null  $graceLogId  UUID GraceLog row, atau null untuk pesan pre-expiry
     *                                   (H-3) yang tidak perlu di-track di tabel grace_logs
     * @param  string  $recipientPhone Nomor WA tujuan
     * @param  string  $waTemplate     Nama view blade plain text (e.g. 'emails.grace.wa.h-plus-1')
     * @param  array   $data           Data yang diteruskan ke view (name, expired_at, dll.)
     */
    public function __construct(
        public ?string $graceLogId,
        public string $recipientPhone,
        public string $waTemplate,
        public array $data
    ) {}

    public function handle(): void
    {
        $log = $this->graceLogId ? GraceLog::find($this->graceLogId) : null;

        if ($this->graceLogId && ! $log) {
            Log::warning('[Grace] SendGraceWhatsappJob: grace_log not found', [
                'grace_log_id' => $this->graceLogId,
            ]);

            return;
        }

        try {
            $session = WhatsappOtpSession::where('status', true)
                ->orderBy('created_at', 'asc')
                ->first();

            if (! $session) {
                $log?->markFailed('no active WhatsApp session');

                Log::warning('[Grace] SendGraceWhatsappJob: no active WA session', [
                    'grace_log_id' => $this->graceLogId,
                ]);

                return;
            }

            if (empty($this->waTemplate) || ! View::exists($this->waTemplate)) {
                $log?->markFailed('wa template missing: '.$this->waTemplate);

                Log::error('[Grace] SendGraceWhatsappJob: WA template missing', [
                    'template' => $this->waTemplate,
                ]);

                return;
            }

            $message = trim((string) view($this->waTemplate, $this->data)->render());

            if ($message === '') {
                $log?->markFailed('rendered WA message is empty');

                return;
            }

            $result = WhatsappHelper::sendTextMessage(
                $session->session,
                $this->recipientPhone,
                $message
            );

            // WhatsappHelper::sendTextMessage returns ['error' => bool, 'data' => mixed]
            $isError = is_array($result)
                ? (bool) ($result['error'] ?? true)
                : ($result === false);

            if ($isError) {
                $reason = is_array($result) ? json_encode($result['data'] ?? null) : 'unknown error';
                $log?->markFailed('WA API error: '.substr((string) $reason, 0, 500));

                Log::error('[Grace] SendGraceWhatsappJob: API error', [
                    'grace_log_id' => $this->graceLogId,
                    'result' => $result,
                ]);

                return;
            }

            $log?->markSent();
        } catch (\Throwable $e) {
            Log::error('[Grace] SendGraceWhatsappJob exception: '.$e->getMessage(), [
                'grace_log_id' => $this->graceLogId,
                'trace' => $e->getTraceAsString(),
            ]);

            $log?->markFailed('exception: '.$e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Grace] SendGraceWhatsappJob permanently failed: '.$e->getMessage(), [
            'grace_log_id' => $this->graceLogId,
        ]);

        if ($this->graceLogId) {
            optional(GraceLog::find($this->graceLogId))
                ->markFailed('job permanently failed: '.$e->getMessage());
        }
    }
}

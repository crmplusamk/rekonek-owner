<?php

namespace App\Jobs\Grace;

use App\Mail\Grace\GraceTouchpointMail;
use App\Models\GraceLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Job mengirim email touchpoint grace period via Mail::send dengan
 * mailable GraceTouchpointMail (dinamis berdasarkan viewName).
 *
 * Update status grace_logs:
 *   - 'sent'   setelah Mail::queue berhasil di-push ke queue emails
 *              (actual delivery tetap async, tapi di-mark sent di sini
 *              karena Laravel tidak provide callback sinkron di Mail)
 *   - 'failed' jika view template missing atau exception
 */
class SendGraceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    /**
     * @param  string  $graceLogId     UUID GraceLog row
     * @param  string  $recipientEmail Email tujuan
     * @param  string  $emailTemplate  Blade view, e.g. 'emails.grace.h-plus-1'
     * @param  string  $subject        Subject email
     * @param  array   $data           Data yang diteruskan ke view
     */
    public function __construct(
        public string $graceLogId,
        public string $recipientEmail,
        public string $emailTemplate,
        public string $subject,
        public array $data
    ) {}

    public function handle(): void
    {
        $log = GraceLog::find($this->graceLogId);

        if (! $log) {
            Log::warning('[Grace] SendGraceEmailJob: grace_log not found', [
                'grace_log_id' => $this->graceLogId,
            ]);

            return;
        }

        try {
            if (empty($this->emailTemplate) || ! View::exists($this->emailTemplate)) {
                $log->markFailed('email template missing: '.$this->emailTemplate);

                Log::error('[Grace] SendGraceEmailJob: template missing', [
                    'template' => $this->emailTemplate,
                ]);

                return;
            }

            Mail::to($this->recipientEmail)->send(
                new GraceTouchpointMail($this->emailTemplate, $this->subject, $this->data)
            );

            $log->markSent();
        } catch (\Throwable $e) {
            Log::error('[Grace] SendGraceEmailJob exception: '.$e->getMessage(), [
                'grace_log_id' => $this->graceLogId,
            ]);

            $log->markFailed('exception: '.$e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Grace] SendGraceEmailJob permanently failed: '.$e->getMessage(), [
            'grace_log_id' => $this->graceLogId,
        ]);

        optional(GraceLog::find($this->graceLogId))
            ->markFailed('job permanently failed: '.$e->getMessage());
    }
}

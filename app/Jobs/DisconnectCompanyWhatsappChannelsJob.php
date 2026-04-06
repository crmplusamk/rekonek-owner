<?php

namespace App\Jobs;

use App\Helpers\Whatsapp\V2\WhatsappHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisconnectCompanyWhatsappChannelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $companyId
    ) {}

    public function handle(): void
    {
        if ($this->companyId === '') {
            return;
        }

        $sessions = DB::connection('client')
            ->table('chat_sessions')
            ->where('company_id', $this->companyId)
            ->where('channel', 'whatsapp')
            ->where('status', 1)
            ->pluck('code');

        if ($sessions->isEmpty()) {
            return;
        }

        foreach ($sessions as $code) {
            if (empty($code)) {
                continue;
            }

            $result = WhatsappHelper::disconnectInstance($code);

            if (($result['status'] ?? '') !== 'success') {
                Log::info('DisconnectCompanyWhatsappChannelsJob: disconnect result', [
                    'company_id' => $this->companyId,
                    'code' => $code,
                    'result' => $result,
                ]);
            }
        }
    }
}

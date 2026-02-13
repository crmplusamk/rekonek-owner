<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;
use App\Traits\AccessLogFollowupTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseFollowupJob implements ShouldBeUnique, ShouldQueue
{
    use AccessLogFollowupTrait;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected AccessLog $accessLog;

    protected string $currentStage;

    protected string $taskName;

    protected string $taskDescription;

    protected array $nextStages;

    public function __construct(AccessLog $accessLog)
    {
        $this->accessLog = $accessLog;
    }

    public function uniqueId(): string
    {
        return $this->currentStage.'_'.($this->accessLog->company_id ?? $this->accessLog->email ?? $this->accessLog->id);
    }

    public function uniqueFor(): int
    {
        return 60 * 60 * 24;
    }

    public function handle(): void
    {
        $customerKey = $this->accessLog->company_id ?? $this->accessLog->email ?? $this->accessLog->id;

        if ($this->hasProgressed($customerKey)) {
            Log::info("Customer already progressed, skipping followup: {$customerKey}");

            return;
        }

        $processedCount = $this->processStuckCustomers(
            $this->accessLog,
            $this->taskName,
            $this->taskDescription
        );

        if ($processedCount > 0) {
            $this->markFollowupSent((object) [
                'company_id' => $this->accessLog->company_id,
                'email' => $this->accessLog->email,
            ], $this->currentStage);
        }
    }

    protected function hasProgressed(string $customerKey): bool
    {
        $query = DB::table('access_logs')
            ->whereIn('progress', $this->nextStages);

        if (! empty($this->accessLog->company_id)) {
            $query->where('company_id', $this->accessLog->company_id);
        } elseif (! empty($this->accessLog->email)) {
            $query->where('email', $this->accessLog->email);
        }

        return $query->exists();
    }
}

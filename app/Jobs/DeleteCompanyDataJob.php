<?php

namespace App\Jobs;

use App\Services\CompanyDataPurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Throwable;

class DeleteCompanyDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    /**
     * Window keunikan: 24 jam.
     * Selama rentang ini, dispatch ulang untuk company_id yang sama akan di-skip.
     */
    public int $uniqueFor = 86400;

    public string $companyId;

    public ?string $backupDirectory;

    public bool $withBackup;

    public function __construct(string $companyId, ?string $backupDirectory = null, bool $withBackup = true)
    {
        $this->companyId = $companyId;
        $this->backupDirectory = $backupDirectory;
        $this->withBackup = $withBackup;

        $this->onConnection(config('queue.default'));
        $this->onQueue('default');
    }

    /**
     * Lock key untuk ShouldBeUnique.
     */
    public function uniqueId(): string
    {
        return 'company-purge:' . $this->companyId;
    }

    public function handle(CompanyDataPurgeService $companyDataPurgeService): void
    {
        if ($this->hasCurrentSubscription()) {
            Log::info('Company purge job skipped because company has current subscription.', [
                'company_id' => $this->companyId,
            ]);

            return;
        }

        Log::info('Company purge job started.', [
            'company_id' => $this->companyId,
            'backup_directory' => $this->backupDirectory,
            'with_backup' => $this->withBackup,
        ]);

        $result = $companyDataPurgeService->purge(
            $this->companyId,
            $this->backupDirectory,
            $this->withBackup
        );

        $this->markContactAsDeleted();

        Log::info('Company purge job completed.', [
            'company_id'    => $this->companyId,
            'backup_path'   => $result['backup_path'],
            'total_deleted' => $result['total_deleted'],
            'tables_count'  => count($result['plan']),
        ]);
    }

    private function markContactAsDeleted(): void
    {
        $updated = DB::table('contacts')
            ->where('company_id', $this->companyId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        Log::info('Contact deleted_at marked after purge.', [
            'company_id' => $this->companyId,
            'updated'    => $updated,
        ]);
    }

    private function hasCurrentSubscription(): bool
    {
        return SubscriptionPackage::forCompany($this->companyId)
            ->currentEffective()
            ->exists();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Company purge job failed.', [
            'company_id' => $this->companyId,
            'error'      => $exception->getMessage(),
            'trace'      => $exception->getTraceAsString(),
        ]);
    }
}

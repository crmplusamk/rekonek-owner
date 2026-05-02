<?php

namespace App\Jobs;

use App\Services\CompanyMongoDataPurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Throwable;

/**
 * Backup + hapus koleksi MongoDB milik satu company.
 * Proses terpisah dari {@see DeleteCompanyDataJob} (PostgreSQL/client DB).
 */
class DeleteCompanyMongoDataJob implements ShouldQueue, ShouldBeUnique
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
        return 'company-purge-mongo:' . $this->companyId;
    }

    public function handle(CompanyMongoDataPurgeService $service): void
    {
        if ($this->hasCurrentSubscription()) {
            Log::info('Company mongo purge job skipped because company has current subscription.', [
                'company_id' => $this->companyId,
            ]);

            return;
        }

        Log::info('Company mongo purge job started.', [
            'company_id' => $this->companyId,
            'backup_directory' => $this->backupDirectory,
            'with_backup' => $this->withBackup,
        ]);

        $result = $service->purge(
            $this->companyId,
            $this->backupDirectory,
            $this->withBackup
        );

        Log::info('Company mongo purge job completed.', [
            'company_id'      => $this->companyId,
            'backup_path'     => $result['backup_path'],
            'total_backed_up' => $result['total_backed_up'],
            'collections'     => $result['collections'],
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
        Log::error('Company mongo purge job failed.', [
            'company_id' => $this->companyId,
            'error'      => $exception->getMessage(),
            'trace'      => $exception->getTraceAsString(),
        ]);
    }
}

<?php

namespace App\Services\GracePeriod;

use App\Jobs\Grace\SendGraceEmailJob;
use App\Jobs\Grace\SendGraceWhatsappJob;
use App\Models\GraceLog;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GraceDripService
 *
 * Bertanggung jawab atas dispatching notifikasi drip per touchpoint:
 *   1. Query kandidat (hanya untuk handler='drip' — kandidat middle touchpoint)
 *   2. Membuat entry `grace_logs` (idempotent) per channel
 *   3. Dispatch queued job (SendGraceWhatsappJob / SendGraceEmailJob)
 *
 * Digunakan oleh:
 *   - DripGraceCommand  → iterate semua touchpoint handler='drip'
 *   - EnterGraceCommand → dispatch touchpoint H+1 setelah transisi state
 *   - TerminateGraceCommand → dispatch touchpoint H+31 setelah transisi state
 */
class GraceDripService
{
    /**
     * Cari kandidat untuk drip touchpoint (handler='drip').
     * Filter: is_grace='grace' DAN DATEDIFF(today, expired_at) = day_offset.
     */
    public function findDripCandidates(
        array $config,
        ?Carbon $today = null,
        ?string $companyIdFilter = null
    ): Collection {
        $today = $today ? $today->copy()->startOfDay() : Carbon::today();
        $dayOffset = (int) ($config['day_offset'] ?? 0);

        // expired_at = today - day_offset days (karena drip dikirim pada hari ke-N setelah expired)
        $targetExpiredDate = $today->copy()->subDays($dayOffset)->toDateString();

        $query = DB::table('subscription_packages as sp')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->where('sp.is_grace', GraceLifecycleService::STATE_GRACE)
            ->whereDate('sp.expired_at', $targetExpiredDate)
            ->whereNotExists(function ($subQuery) use ($today) {
                $subQuery->select(DB::raw(1))
                    ->from('subscription_packages as current_sp')
                    ->whereColumn('current_sp.company_id', 'sp.company_id')
                    ->where('current_sp.is_active', true)
                    ->where('current_sp.is_grace', GraceLifecycleService::STATE_ACTIVE)
                    ->whereDate('current_sp.started_at', '<=', $today->toDateString())
                    ->whereDate('current_sp.expired_at', '>=', $today->toDateString());
            })
            ->select([
                'sp.id as subscription_package_id',
                'sp.company_id',
                'sp.grace_started_at',
                'sp.expired_at',
                'c.email',
                'c.name',
                'c.phone',
            ]);

        if ($companyIdFilter) {
            $query->where('sp.company_id', $companyIdFilter);
        }

        return $query->get();
    }

    /**
     * Proses 1 target (1 row hasil query) — buat grace_logs + dispatch job per channel.
     *
     * @param  object  $row  stdClass dari DB query, harus punya: subscription_package_id, company_id,
     *                       grace_started_at, expired_at, email, name, phone
     * @param  string  $touchpointKey  e.g. 'H+1'
     * @param  array   $config  Entry config touchpoint
     * @param  array   $options  ['dry_run' => bool, 'force' => bool]
     * @return array  List of channels yang berhasil di-dispatch
     */
    public function dispatchForTarget(
        object $row,
        string $touchpointKey,
        array $config,
        array $options = []
    ): array {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);

        $channels = array_values(array_intersect(
            $config['channels'] ?? [],
            [GraceLog::CHANNEL_WA, GraceLog::CHANNEL_EMAIL]
        ));

        $dispatched = [];

        foreach ($channels as $channel) {

            if ($channel === GraceLog::CHANNEL_WA && empty($row->phone)) {
                Log::warning('[Grace] skip WA: phone empty', [
                    'company_id' => $row->company_id,
                    'touchpoint' => $touchpointKey,
                ]);
                continue;
            }

            if ($channel === GraceLog::CHANNEL_EMAIL && empty($row->email)) {
                Log::warning('[Grace] skip email: email empty', [
                    'company_id' => $row->company_id,
                    'touchpoint' => $touchpointKey,
                ]);
                continue;
            }

            if ($dryRun) {
                $dispatched[] = $channel.' (dry-run)';
                continue;
            }

            $log = $this->createOrReuseLog($row, $touchpointKey, $channel, $force);
            if (! $log) {
                // Sudah ada log sebelumnya untuk cycle ini + !force → skip
                continue;
            }

            $data = $this->buildData($row, $touchpointKey, $config);

            if ($channel === GraceLog::CHANNEL_WA) {
                SendGraceWhatsappJob::dispatch(
                    (string) $log->id,
                    (string) $row->phone,
                    (string) ($config['wa_template'] ?? ''),
                    $data
                )->onQueue('default');
            } elseif ($channel === GraceLog::CHANNEL_EMAIL) {
                SendGraceEmailJob::dispatch(
                    (string) $log->id,
                    (string) $row->email,
                    (string) ($config['email_template'] ?? ''),
                    (string) ($config['email_subject'] ?? 'Notifikasi Langganan'),
                    $data
                )->onQueue('emails');
            }

            $dispatched[] = $channel;
        }

        return $dispatched;
    }

    /**
     * firstOrCreate equivalent untuk grace_logs.
     * Return null jika sudah ada entry dan !$force (untuk memicu skip).
     */
    protected function createOrReuseLog(object $row, string $touchpointKey, string $channel, bool $force): ?GraceLog
    {
        $graceStartedAt = $row->grace_started_at instanceof \DateTimeInterface
            ? $row->grace_started_at->format('Y-m-d')
            : (string) $row->grace_started_at;

        $criteria = [
            'company_id' => (string) $row->company_id,
            'grace_started_at' => $graceStartedAt,
            'touchpoint_key' => $touchpointKey,
            'channel' => $channel,
        ];

        try {
            $existing = GraceLog::where($criteria)->first();

            if ($existing) {
                if (! $force) {
                    return null;
                }

                $existing->update([
                    'status' => GraceLog::STATUS_QUEUED,
                    'sent_at' => null,
                    'error_message' => null,
                ]);

                return $existing;
            }

            return GraceLog::create(array_merge($criteria, [
                'subscription_package_id' => (string) $row->subscription_package_id,
                'status' => GraceLog::STATUS_QUEUED,
            ]));
        } catch (QueryException $e) {
            // UNIQUE violation karena race (2 worker paralel) — acceptable
            Log::warning('[Grace] createOrReuseLog failed (likely duplicate)', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build data array yang diteruskan ke Blade view (email + WA).
     */
    protected function buildData(object $row, string $touchpointKey, array $config): array
    {
        return [
            'name' => $row->name ?? 'Customer',
            'company_id' => (string) $row->company_id,
            'expired_at' => $row->expired_at,
            'grace_started_at' => $row->grace_started_at ?? null,
            'touchpoint_key' => $touchpointKey,
            'phase' => $config['phase'] ?? null,
            'subscription_package_id' => (string) $row->subscription_package_id,
        ];
    }
}

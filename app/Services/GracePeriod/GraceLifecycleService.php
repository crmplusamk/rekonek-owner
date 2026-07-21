<?php

namespace App\Services\GracePeriod;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * GraceLifecycleService
 *
 * Bertanggung jawab atas transisi state kolom `is_grace` di tabel
 * subscription_packages, dan menyediakan query helper untuk mencari
 * kandidat user yang perlu masuk/keluar grace period.
 *
 * State transitions:
 *   - active     → grace      : saat expired_at = kemarin (dijalankan EnterGraceCommand)
 *   - grace      → end_grace  : saat grace_started_at + 30 hari <= hari ini (TerminateGraceCommand)
 *   - grace      → active     : saat user renew (dihandle di CheckoutApiController, bukan di sini)
 */
class GraceLifecycleService
{
    public const STATE_ACTIVE = 'active';

    public const STATE_GRACE = 'grace';

    public const STATE_END_GRACE = 'end_grace';

    /**
     * Default grace period length dalam hari (H+1 masuk grace s/d H+30 inclusive → H+31 terminate).
     */
    public const GRACE_DURATION_DAYS = 30;

    /**
     * Lookback window (hari) untuk kandidat enter-grace. >1 membuat command self-healing bila
     * eksekusi harian terlewat: row yang expired dalam N hari terakhir & masih is_grace='active'
     * tetap tertangkap, bukan hanya "kemarin persis". Aman karena filter is_grace='active' (row yang
     * sudah masuk grace tak akan terpilih lagi) + whereNotExists currentEffective (yang sudah
     * perpanjang dikecualikan) → hanya menangkap row yang benar-benar terlewat.
     */
    public const ENTER_GRACE_LOOKBACK_DAYS = 2;

    /**
     * Cari subscription_packages yang expired_at dalam ENTER_GRACE_LOOKBACK_DAYS hari terakhir
     * (mis. 2 hari: kemarin & sehari sebelumnya) DAN is_grace = 'active'. Mereka adalah kandidat
     * transisi ke state 'grace' (dispatched oleh EnterGraceCommand).
     */
    public function findEnterGraceCandidates(
        ?Carbon $today = null,
        ?string $companyIdFilter = null
    ): Collection {
        $today = $today ? $today->copy()->startOfDay() : Carbon::today();
        $yesterday = $today->copy()->subDay()->toDateString();
        $lookbackFrom = $today->copy()->subDays(self::ENTER_GRACE_LOOKBACK_DAYS)->toDateString();

        $query = DB::table('subscription_packages as sp')
            ->join('packages as p', 'p.id', '=', 'sp.package_id')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->whereDate('sp.expired_at', '>=', $lookbackFrom)
            ->whereDate('sp.expired_at', '<=', $yesterday)
            ->where('sp.is_grace', self::STATE_ACTIVE)
            ->whereNotExists(function ($subQuery) use ($today) {
                $subQuery->select(DB::raw(1))
                    ->from('subscription_packages as current_sp')
                    ->whereColumn('current_sp.company_id', 'sp.company_id')
                    ->where('current_sp.is_active', true)
                    ->where('current_sp.is_grace', self::STATE_ACTIVE)
                    ->whereDate('current_sp.started_at', '<=', $today->toDateString())
                    ->whereDate('current_sp.expired_at', '>=', $today->toDateString());
            })
            ->select([
                'sp.id as subscription_package_id',
                'sp.company_id',
                'sp.expired_at',
                'c.email',
                'c.name',
                'c.phone',
                'p.name as package_name',
            ])
            ->orderBy('sp.expired_at');

        if ($companyIdFilter) {
            $query->where('sp.company_id', $companyIdFilter);
        }

        return $query->get();
    }

    /**
     * Cari subscription_packages yang is_grace = 'grace' dan sudah mencapai
     * GRACE_DURATION_DAYS sejak grace_started_at.
     */
    public function findTerminateCandidates(
        ?Carbon $today = null,
        ?string $companyIdFilter = null
    ): Collection {
        $today = $today ? $today->copy()->startOfDay() : Carbon::today();
        $cutoff = $today->copy()->subDays(self::GRACE_DURATION_DAYS)->toDateString();

        $query = DB::table('subscription_packages as sp')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->where('sp.is_grace', self::STATE_GRACE)
            ->whereDate('sp.grace_started_at', '<=', $cutoff)
            ->whereNotExists(function ($subQuery) use ($today) {
                $subQuery->select(DB::raw(1))
                    ->from('subscription_packages as current_sp')
                    ->whereColumn('current_sp.company_id', 'sp.company_id')
                    ->where('current_sp.is_active', true)
                    ->where('current_sp.is_grace', self::STATE_ACTIVE)
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
     * Transisi state: active → grace
     * Menjalankan juga is_active = false (sudah konsisten dengan expired_at lewat).
     */
    public function transitionToGrace(string $subscriptionPackageId, ?Carbon $graceStartedAt = null): bool
    {
        $graceStartedAt = $graceStartedAt ? $graceStartedAt->toDateString() : Carbon::today()->toDateString();

        return SubscriptionPackage::where('id', $subscriptionPackageId)
            ->where('is_grace', self::STATE_ACTIVE)
            ->update([
                'is_grace' => self::STATE_GRACE,
                'grace_started_at' => $graceStartedAt,
                'is_active' => false,
            ]) > 0;
    }

    /**
     * Transisi state: grace → end_grace
     * Hook untuk data deletion pipeline (di luar scope dokumen desain saat ini).
     */
    public function transitionToEndGrace(string $subscriptionPackageId): bool
    {
        return SubscriptionPackage::where('id', $subscriptionPackageId)
            ->where('is_grace', self::STATE_GRACE)
            ->update([
                'is_grace' => self::STATE_END_GRACE,
            ]) > 0;
    }
}

<?php

namespace Modules\Subscription\App\Models;

use App\Traits\UuidTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\App\Models\Customer;
use Modules\Package\App\Models\Package;

class SubscriptionPackage extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'subscription_packages';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }

    public function scopeForCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeValidRecord(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeGraceActive(Builder $query): Builder
    {
        return $query->where('is_grace', 'active');
    }

    public function scopeEffectiveOn(Builder $query, Carbon|string|null $date = null): Builder
    {
        $date = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();

        return $query
            ->whereDate('started_at', '<=', $date)
            ->whereDate('expired_at', '>=', $date);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('expired_at')
            ->orderByDesc('started_at')
            ->orderByDesc('created_at');
    }

    public function scopeCurrentEffective(Builder $query, Carbon|string|null $date = null): Builder
    {
        return $query
            ->validRecord()
            ->graceActive()
            ->effectiveOn($date)
            ->latestFirst();
    }

    /**
     * SUMBER KEBENARAN "langganan yang DIPAKAI" untuk sebuah company — dipadankan dengan resolver
     * entitlement rekonek-app (SubscriptionService::getAuthData): ambil row `is_active=true` dengan
     * `expired_at` TERJAUH. Tie-break started_at & created_at (via latestFirst) agar deterministik;
     * app hanya urut expired_at desc, jadi row pertama selalu sama.
     *
     * Gunakan scope INI untuk menandai/menentukan langganan aktif yang benar-benar dipakai app.
     * BEDAKAN dengan scopeCurrentEffective yang LEBIH KETAT (+ grace='active' + berlaku hari ini) —
     * itu untuk logika billing/grace "efektif hari ini", BUKAN untuk "yang dipakai app".
     */
    public function scopeActiveResolved(Builder $query): Builder
    {
        return $query->validRecord()->latestFirst();
    }
}

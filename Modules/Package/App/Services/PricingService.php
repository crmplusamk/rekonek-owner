<?php

namespace Modules\Package\App\Services;

use Carbon\Carbon;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\Services\AddonTierPricingService;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * SUMBER KEBENARAN harga (server-side) untuk paket & addon.
 * Menggantikan perhitungan harga di client (JS) yang dulu dipercaya apa adanya oleh owner.
 *
 * Dipakai oleh: CheckoutService (checkout paket/addon) & RenewalQuoteService (invoice renew).
 *
 * Aturan (identik dengan formula client sebelumnya):
 * - Paket bulanan = price; tahunan = price×12 − 20%, floor ke ribuan.
 * - Addon bundled (beli bareng paket): bulanan = price; tahunan = price×12 (TANPA diskon 20%).
 * - Addon-only mid-cycle (termin='day'): recurring = floor(price × sisaHari/30); onetime = price penuh.
 * - subtotal = unitPrice × quantity (blok).
 *
 * Tier diskon addon (addon_price_tiers) diterapkan pada base price sebelum termin/prorata.
 */
class PricingService
{
    public function __construct(private AddonTierPricingService $tierPricing)
    {
    }

    /** Harga paket per termin. */
    public function packagePrice(float $monthlyPrice, ?string $termin): float
    {
        if ($this->isYear($termin)) {
            $yearlyWithDiscount = ($monthlyPrice * 12) - (($monthlyPrice * 12) * 20 / 100);

            return floor($yearlyWithDiscount / 1000) * 1000;
        }

        return $monthlyPrice;
    }

    /**
     * Harga addon per unit (blok) — SEBELUM dikali quantity.
     *
     * @param Addon $addon
     * @param string|null $termin 'day' (addon-only prorata) | 'month' | 'year'
     * @param int $quantity jumlah blok (untuk resolusi tier diskon)
     * @param string|null $companyId untuk hitung sisa hari (prorata) dari langganan aktif
     */
    public function addonUnitPrice(Addon $addon, ?string $termin, int $quantity, ?string $companyId = null): float
    {
        // Base = harga tier (bila ada aturan diskon yg cocok utk quantity) atau harga master.
        $base = $this->tierPricing->unitPrice($addon, $quantity);

        return $this->applyAddonTermin($addon, $base, $termin, $companyId);
    }

    /** Harga per blok TANPA diskon tier (untuk tampilan harga coret). */
    public function addonNormalUnitPrice(Addon $addon, ?string $termin, ?string $companyId = null): float
    {
        return $this->applyAddonTermin($addon, (float) $addon->price, $termin, $companyId);
    }

    /** Terapkan termin/prorata pada suatu base price per blok. */
    private function applyAddonTermin(Addon $addon, float $base, ?string $termin, ?string $companyId): float
    {
        $isOnetime = $addon->billing_type === Addon::BILLING_ONETIME;

        // Addon-only mid-cycle: prorata sisa hari (recurring) atau penuh (onetime).
        if ($this->isDay($termin)) {
            if ($isOnetime) {
                return $base;
            }

            $days = $this->prorationDays($companyId);

            return floor($base * $days / 30);
        }

        // Bundled tahunan: ×12 (tanpa diskon 20%).
        if ($this->isYear($termin)) {
            return $base * 12;
        }

        // Bundled bulanan.
        return $base;
    }

    /**
     * Sisa hari langganan aktif (untuk prorata addon-only). Dipadankan dgn client:
     * subsExpired(start of day) − today(start of day), tidak negatif.
     */
    public function prorationDays(?string $companyId): int
    {
        if (! $companyId) {
            return 0;
        }

        $subs = SubscriptionPackage::forCompany($companyId)->activeResolved()->first();
        if (! $subs || ! $subs->expired_at) {
            return 0;
        }

        $end = Carbon::parse($subs->expired_at)->startOfDay();
        $today = Carbon::now()->startOfDay();

        return $today->lte($end) ? $today->diffInDays($end) : 0;
    }

    private function isYear(?string $termin): bool
    {
        return in_array($termin, ['year', 'yearly'], true);
    }

    private function isDay(?string $termin): bool
    {
        return $termin === 'day';
    }
}

<?php

namespace Modules\Subscription\App\Services;

use Carbon\Carbon;
use Modules\Addon\App\Models\Addon;
use Modules\Package\App\Models\Package;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * SUMBER KEBENARAN perhitungan tagihan perpanjangan (renewal).
 *
 * Dipakai oleh:
 * - GenerateRenewalInvoiceJob (buat invoice renew H-7) — memakai `items` apa adanya.
 * - API `GET /api/v1/subscription/{companyId}/renewal-quote` — dikonsumsi rekonek-app
 *   untuk display "Nilai Langganan" (= tagihan invoice perpanjangan berikutnya).
 *
 * Aturan harga:
 * - Paket: bulanan = price; tahunan = price×12 − 20%, floor ke ribuan terdekat.
 * - Addon: bulanan = price per blok; tahunan = price×12 (tanpa diskon).
 * - subscription_addons.charge = total UNIT; ukuran blok = addons.charge
 *   (Nomor WA/CS Agent = 1, MAU/AI Credit = 1000). blocks = unit ÷ ukuran_blok.
 * - Hanya addon is_active && expired_at >= hari ini yang ikut ditagih.
 */
class RenewalQuoteService
{
    /**
     * Quote untuk company (pakai langganan efektif yang dipakai app: activeResolved).
     */
    public function quoteForCompany(string $companyId): ?array
    {
        $subsPackage = SubscriptionPackage::forCompany($companyId)
            ->activeResolved()
            ->with('package')
            ->first();

        if (! $subsPackage) {
            return null;
        }

        return $this->quoteForSubscription($subsPackage);
    }

    /**
     * @return array{
     *   termin: string, termin_duration: int,
     *   package: array, addons: array[], items: array[],
     *   subtotal: float, total: float
     * }|null
     */
    public function quoteForSubscription(SubscriptionPackage $subsPackage): ?array
    {
        $package = $subsPackage->package;
        if (! $package) {
            return null;
        }

        $termin = $this->normalizeTermin($subsPackage->termin);
        $terminDuration = (int) ($subsPackage->termin_duration ?? 1);

        $endDate = Carbon::parse($subsPackage->expired_at)
            ->add($terminDuration, $termin . 's')
            ->format('Y-m-d H:i:s');

        $items = [];
        $subtotal = 0;

        /** package item */
        $packagePrice = $this->calculatePackagePrice((float) $package->price, $termin);
        $items[] = [
            'modelable_id' => $package->id,
            'modelable_type' => Package::class,
            'duration' => $terminDuration,
            'duration_type' => $termin,
            'termin' => $termin,
            'termin_duration' => $terminDuration,
            'quantity' => 1,
            'price' => $packagePrice,
            'subtotal' => $packagePrice,
            'start_date' => $subsPackage->expired_at,
            'end_date' => $endDate,
        ];
        $subtotal += $packagePrice;

        /** addon items (aktif & belum kadaluarsa) */
        $addonSummaries = [];
        $subscriptionAddons = SubscriptionAddon::where('company_id', $subsPackage->company_id)
            ->where('is_active', true)
            ->whereDate('expired_at', '>=', Carbon::now())
            ->with('addon.feature')
            ->get();

        foreach ($subscriptionAddons as $subsAddon) {
            $addon = $subsAddon->addon;
            if (! $addon) {
                continue;
            }

            $addonPrice = $this->calculateAddonPrice((float) $addon->price, $termin);
            $blockSize = max(1, (int) $addon->charge);
            $units = max(1, (int) ($subsAddon->charge ?? 1));
            $blocks = max(1, intdiv($units, $blockSize));
            $lineSubtotal = $addonPrice * $blocks;

            $items[] = [
                'modelable_id' => $addon->id,
                'modelable_type' => Addon::class,
                'duration' => $terminDuration,
                'duration_type' => $termin,
                'termin' => $termin,
                'termin_duration' => $terminDuration,
                'quantity' => $blocks,
                'charge' => $units, // TETAP unit — dibaca settlement (updateAddon)
                'price' => $addonPrice,
                'subtotal' => $lineSubtotal,
                'start_date' => $subsPackage->expired_at,
                'end_date' => $endDate,
            ];
            $subtotal += $lineSubtotal;

            $addonSummaries[] = [
                'addon_id' => $addon->id,
                'name' => $addon->name,
                'key' => $addon->feature->key ?? null,
                'units' => $units,
                'block_size' => $blockSize,
                'blocks' => $blocks,
                'price' => $addonPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        return [
            'subscription_id' => $subsPackage->id,
            'company_id' => $subsPackage->company_id,
            'termin' => $termin,
            'termin_duration' => $terminDuration,
            'expired_at' => (string) $subsPackage->expired_at,
            'package' => [
                'package_id' => $package->id,
                'name' => $package->name,
                'price' => (float) $package->price,
                'subtotal' => $packagePrice,
            ],
            'addons' => $addonSummaries,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => (float) floor($subtotal),
        ];
    }

    public function normalizeTermin(?string $termin): string
    {
        $termin = $termin ?: 'month';
        if ($termin === 'monthly') return 'month';
        if ($termin === 'yearly') return 'year';

        return $termin;
    }

    /** Paket: bulanan = price; tahunan = price×12 − 20%, floor ke ribuan. */
    public function calculatePackagePrice(float $monthlyPrice, string $termin): float
    {
        if ($termin === 'month' || $termin === 'monthly') {
            return $monthlyPrice;
        }

        $yearlyWithDiscount = ($monthlyPrice * 12) - (($monthlyPrice * 12) * 20 / 100);

        return floor($yearlyWithDiscount / 1000) * 1000;
    }

    /** Addon: bulanan = price per blok; tahunan = price×12 (tanpa diskon). */
    public function calculateAddonPrice(float $monthlyPrice, string $termin): float
    {
        if ($termin === 'month' || $termin === 'monthly') {
            return $monthlyPrice;
        }

        return $monthlyPrice * 12;
    }
}

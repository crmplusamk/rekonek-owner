<?php

namespace Modules\Addon\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Addon\App\Models\Addon;

/**
 * Resolusi harga per-blok addon dengan aturan diskon tier (addon_price_tiers).
 * Generik untuk semua addon; bila addon tak punya tier aktif → harga master.
 */
class AddonTierPricingService
{
    /**
     * Harga per blok efektif untuk quantity (jumlah blok) tertentu.
     */
    public function unitPrice(Addon $addon, int $quantity): float
    {
        $base = (float) $addon->price;
        $tier = $this->resolveTier((string) $addon->id, $quantity);

        if (! $tier) {
            return $base;
        }

        if ($tier->type === 'percent') {
            return max(0, $base * (1 - ((float) $tier->value / 100)));
        }

        // unit_price
        return (float) $tier->value;
    }

    /**
     * Tier diskon aktif dengan min_quantity terbesar yang <= quantity, atau null.
     */
    public function resolveTier(string $addonId, int $quantity): ?object
    {
        if (! Schema::hasTable('addon_price_tiers')) {
            return null;
        }

        return DB::table('addon_price_tiers')
            ->where('addon_id', $addonId)
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('min_quantity')
            ->first();
    }

    /**
     * Semua tier aktif untuk addon (urut naik), untuk ditampilkan/di-preview.
     *
     * @return array<int, object>
     */
    public function tiersFor(string $addonId): array
    {
        if (! Schema::hasTable('addon_price_tiers')) {
            return [];
        }

        return DB::table('addon_price_tiers')
            ->where('addon_id', $addonId)
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get()
            ->all();
    }
}

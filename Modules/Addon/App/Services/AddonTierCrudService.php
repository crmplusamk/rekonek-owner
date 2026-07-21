<?php

namespace Modules\Addon\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\Models\AddonPriceTier;

/**
 * CRUD Aturan Diskon (Price Tier) untuk katalog addon (service pattern, sejalan dengan
 * AddonCrudService). Sengaja dipisah dari AddonTierPricingService — service tsb adalah
 * RESOLVER harga yang dipakai checkout (jangan diubah); service ini murni admin CRUD di
 * atas tabel yang sama (addon_price_tiers).
 */
class AddonTierCrudService
{
    public function getAddonOrFail(string $addonId): Addon
    {
        return Addon::findOrFail($addonId);
    }

    public function find(string $id): AddonPriceTier
    {
        return AddonPriceTier::findOrFail($id);
    }

    /** Semua tier addon (aktif & nonaktif), urut min_quantity naik — untuk tabel kelola admin. */
    public function allFor(string $addonId): Collection
    {
        return AddonPriceTier::where('addon_id', $addonId)
            ->orderBy('min_quantity')
            ->get();
    }

    public function create(array $data): AddonPriceTier
    {
        return AddonPriceTier::create([
            'addon_id' => $data['addon_id'],
            'min_quantity' => $data['min_quantity'],
            'type' => $data['type'],
            'value' => $data['value'],
            'label' => $data['label'] ?? null,
            'is_active' => $data['is_active'] ?? true, // tier baru default aktif (toggle via menu aksi)
        ]);
    }

    public function update(array $data, string $id): AddonPriceTier
    {
        $tier = AddonPriceTier::findOrFail($id);
        $tier->update([
            'min_quantity' => $data['min_quantity'],
            'type' => $data['type'],
            'value' => $data['value'],
            'label' => $data['label'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);
        return $tier;
    }

    public function toggleStatus(string $id): AddonPriceTier
    {
        $tier = AddonPriceTier::findOrFail($id);
        $tier->update(['is_active' => ! $tier->is_active]);
        return $tier;
    }

    public function delete(string $id): bool
    {
        AddonPriceTier::findOrFail($id)->delete();
        return true;
    }
}

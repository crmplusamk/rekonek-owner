<?php

namespace Modules\Addon\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Aturan diskon (price tier) per addon. Lihat migration create_addon_price_tiers_table
 * untuk aturan resolusi (min_quantity terbesar yang <= quantity yang dipakai).
 *
 * Model ini HANYA dipakai untuk CRUD admin (Modules\Addon\App\Services\AddonTierCrudService).
 * Resolusi harga saat checkout tetap lewat Modules\Addon\App\Services\AddonTierPricingService
 * (query manual via DB facade, tidak lewat model ini) — jangan diubah.
 */
class AddonPriceTier extends Model
{
    use UuidTrait;

    public const TYPE_UNIT_PRICE = 'unit_price';
    public const TYPE_PERCENT = 'percent';

    protected $table = 'addon_price_tiers';

    protected $guarded = [];

    protected $casts = [
        'min_quantity' => 'integer',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id', 'id');
    }
}

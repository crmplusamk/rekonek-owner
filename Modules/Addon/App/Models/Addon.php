<?php

namespace Modules\Addon\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Feature\App\Models\Feature;
use Modules\Subscription\App\Models\SubscriptionAddon;

class Addon extends Model
{
    use HasFactory, UuidTrait;

    /**
     * Tipe billing addon (kolom billing_type). Mengatur perilaku harga/proration/renewal/akumulasi
     * (lihat migrasi add_billing_type_to_addons_table). BUKAN identitas fitur — metering AI Credit
     * tetap pakai feature.key = 'AICRD'.
     */
    public const BILLING_RECURRING = 'recurring'; // co-terminous paket, prorata, rebill renewal, kapasitas dipertahankan
    public const BILLING_ONETIME = 'onetime';     // sekali beli / prepaid (AI Credit): harga penuh, carry-over, akumulasi

    protected $table = 'addons';

    protected $guarded = [];

    public function isOneTime(): bool
    {
        return $this->billing_type === self::BILLING_ONETIME;
    }

    // public function invoiceItems()
    // {
    //     return $this->morphMany(SubscriptionInvoiceItem::class, 'itemable');
    // }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    public function subscriptionAddons()
    {
        return $this->hasMany(SubscriptionAddon::class, 'addon_id', 'id');
    }

    /** Aturan diskon (price tier) milik addon ini, urut min_quantity naik. Untuk CRUD admin saja. */
    public function priceTiers()
    {
        return $this->hasMany(AddonPriceTier::class, 'addon_id', 'id')->orderBy('min_quantity');
    }
}

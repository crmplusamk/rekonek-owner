<?php

namespace Modules\PromoCode\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\UuidTrait;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes, UuidTrait;

    protected $table = 'promo_codes';

    protected $guarded = [];

    protected $casts = [
        'discount_percentage' => 'integer',
        'discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'discount_percentage_registrasi' => 'integer',
        'discount_amount_registrasi' => 'decimal:2',
        'min_purchase_registrasi' => 'decimal:2',
        'max_discount_registrasi' => 'decimal:2',
        'discount_percentage_perpanjangan' => 'integer',
        'discount_amount_perpanjangan' => 'decimal:2',
        'min_purchase_perpanjangan' => 'decimal:2',
        'max_discount_perpanjangan' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'per_user_limit' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function affiliatorUser()
    {
        return $this->belongsTo(\Modules\User\App\Models\User::class, 'affiliator_user_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount): float
    {
        if (! $this->isAvailable()) {
            return 0;
        }

        if ($this->min_purchase && $amount < $this->min_purchase) {
            return 0;
        }

        $discount = 0;

        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_percentage) / 100;
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } else {
            $discount = $this->discount_amount;
        }

        return min($discount, $amount);
    }

    public function canBeUsedBy($customerId = null, $companyId = null): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        if ($this->per_user_limit > 0) {
            $usageCount = $this->usages()
                ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->count();

            if ($usageCount >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get discount config for checkout context.
     * is_perpanjangan false → registrasi (pembelian baru). is_perpanjangan true → perpanjangan.
     */
    public function getDiscountForContext(bool $isPerpanjangan): array
    {
        if ($isPerpanjangan) {
            $type = $this->discount_type_perpanjangan ?? 'percentage';
            $percentage = $this->discount_percentage_perpanjangan;
            $amount = $this->discount_amount_perpanjangan;
            $minPurchase = $this->min_purchase_perpanjangan;
            $maxDiscount = $this->max_discount_perpanjangan;
        } else {
            $type = $this->discount_type_registrasi ?? 'percentage';
            $percentage = $this->discount_percentage_registrasi;
            $amount = $this->discount_amount_registrasi;
            $minPurchase = $this->min_purchase_registrasi;
            $maxDiscount = $this->max_discount_registrasi;
        }

        return [
            'discount_type' => $type,
            'discount_percentage' => $percentage,
            'discount_amount' => $amount,
            'min_purchase' => $minPurchase,
            'max_discount' => $maxDiscount,
        ];
    }

    /**
     * Calculate discount amount for checkout context. false = registrasi, true = perpanjangan.
     */
    public function calculateDiscountForContext(float $amount, bool $isPerpanjangan): float
    {
        if (! $this->isAvailable()) {
            return 0;
        }

        $config = $this->getDiscountForContext($isPerpanjangan);
        $minPurchase = $config['min_purchase'];
        if ($minPurchase !== null && (float) $minPurchase > 0 && $amount < (float) $minPurchase) {
            return 0;
        }

        $discount = 0;
        if (($config['discount_type'] ?? 'percentage') === 'percentage') {
            $pct = (float) ($config['discount_percentage'] ?? 0);
            $discount = ($amount * $pct) / 100;
            $maxDiscount = $config['max_discount'];
            if ($maxDiscount !== null && $discount > (float) $maxDiscount) {
                $discount = (float) $maxDiscount;
            }
        } else {
            $discount = (float) ($config['discount_amount'] ?? 0);
        }

        return min($discount, $amount);
    }
}

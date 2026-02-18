<?php

namespace Modules\PromoCode\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\UuidTrait;

class PromoCodeUsage extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'promo_code_usages';

    protected $guarded = [];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'metadata' => 'array',
        'is_ref' => 'boolean',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }
}

<?php

namespace Modules\Referral\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\UuidTrait;

class ReferralUsage extends Model
{
    use HasFactory, UuidTrait;

    protected $guarded = [];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }
}

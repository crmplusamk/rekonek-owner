<?php

namespace Modules\Voucher\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'user_id',
        'company_id',
        'order_id',
        'discount_amount',
        'purchase_amount',
        'metadata',
    ];
    
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}

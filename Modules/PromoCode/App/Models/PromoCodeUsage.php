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

    /** Invoice (untuk usage B/P). */
    public function invoice()
    {
        return $this->belongsTo(\Modules\Invoices\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    /** Contact saat registrasi (usage R) — contact_id → contacts.id. */
    public function registerContact()
    {
        return $this->belongsTo(\App\Models\Contact::class, 'contact_id', 'id');
    }

    /** Contact/customer saat checkout (usage B/P) — customer_id → contacts.id. */
    public function customerContact()
    {
        return $this->belongsTo(\App\Models\Contact::class, 'customer_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $table = 'subscription_invoices';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(SubscriptionInvoiceItem::class);
    }
}

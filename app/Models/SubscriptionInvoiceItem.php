<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'subscription_invoice_items';
    public $incrementing = false;

    protected $guarded = [];

    public function itemable()
    {
        return $this->morphTo();
    }
}

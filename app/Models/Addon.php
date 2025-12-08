<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $table = 'addons';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    public function invoiceItems()
    {
        return $this->morphMany(SubscriptionInvoiceItem::class, 'itemable');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }
}

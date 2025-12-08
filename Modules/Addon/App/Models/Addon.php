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

    protected $table = 'addons';

    protected $guarded = [];

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
}

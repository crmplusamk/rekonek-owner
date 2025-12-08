<?php

namespace Modules\Subscription\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Addon\App\Models\Addon;
use Modules\Customer\App\Models\Customer;

class SubscriptionAddon extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'subscription_addons';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id', 'id');
    }
}

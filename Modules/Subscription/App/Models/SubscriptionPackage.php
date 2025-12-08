<?php

namespace Modules\Subscription\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\App\Models\Customer;
use Modules\Package\App\Models\Package;

class SubscriptionPackage extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'subscription_packages';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }
}

<?php

namespace Modules\Subscription\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Feature\App\Models\Feature;

class SubscriptionFeatureRule extends Model
{
    use UuidTrait;

    protected $table = 'subscription_feature_rules';

    protected $guarded = [];

    protected $casts = [
        'included' => 'boolean',
        'visiblity' => 'boolean',
    ];

    public function subscription()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'subscription_id', 'id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }
}

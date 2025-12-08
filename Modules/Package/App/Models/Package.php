<?php

namespace Modules\Package\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Feature\App\Models\Feature;
use Modules\Subscription\App\Models\Subscription;

class Package extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'packages';

    protected $guarded = [];

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'package_feature', 'package_id', 'feature_id')
            ->withPivot(['limit', 'limit_type', 'included', 'visiblity']);
    }
}

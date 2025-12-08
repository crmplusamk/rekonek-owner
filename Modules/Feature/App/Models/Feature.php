<?php

namespace Modules\Feature\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Addon\App\Models\Addon;

class Feature extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'features';

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(Feature::class, 'parent_id', 'id');
    }

    public function childs()
    {
        return $this->hasMany(Feature::class, 'parent_id', 'id');
    }

    public function addon()
    {
        return $this->hasOne(Addon::class, 'feature_id', 'id');
    }
}

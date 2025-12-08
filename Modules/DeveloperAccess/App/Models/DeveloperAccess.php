<?php

namespace Modules\DeveloperAccess\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeveloperAccess extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'setting_developer_access';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];
}

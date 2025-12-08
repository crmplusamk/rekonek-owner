<?php

namespace Modules\Verification\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Verification\Database\factories\SettingApiFactory;

class SettingApi extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'setting_apis';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

}

<?php

namespace Modules\Logs\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'logs';

    protected $guarded = [];
}

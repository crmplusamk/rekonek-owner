<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'access_logs';

    protected $guarded = [];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}


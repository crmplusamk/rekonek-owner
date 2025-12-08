<?php

namespace Modules\Verification\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Verification\Database\factories\RegistrationTokenFactory;

class RegistrationToken extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'registration_tokens';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];
}

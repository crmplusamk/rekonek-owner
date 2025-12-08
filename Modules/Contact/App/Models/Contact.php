<?php

namespace Modules\Contact\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];
}

<?php

namespace Modules\Deleted\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeletedCompany extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'deleted_companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];
}

<?php

namespace Modules\Privilege\App\Models;

use App\Traits\UuidTrait;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use UuidTrait;

    protected $table = 'permissions';

    protected $guarded = [];
}

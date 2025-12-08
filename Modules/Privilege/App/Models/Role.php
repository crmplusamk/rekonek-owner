<?php

namespace Modules\Privilege\App\Models;

use App\Traits\UuidTrait;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use UuidTrait;

    protected $table = 'roles';

    protected $guarded = [];

}

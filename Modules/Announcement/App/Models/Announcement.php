<?php

namespace Modules\Announcement\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UuidTrait;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';

    public const TARGET_COMPANY = 'company';
    public const TARGET_ROLE = 'role';
    public const TARGET_USER = 'user';

    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_DANGER = 'danger';

    protected $table = 'announcements';

    protected $guarded = [];

    protected $casts = [
        'priority' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }
}

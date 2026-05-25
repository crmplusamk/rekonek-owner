<?php

namespace Modules\Announcement\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTarget extends Model
{
    use HasFactory;
    use UuidTrait;

    protected $table = 'announcement_targets';

    protected $guarded = [];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}

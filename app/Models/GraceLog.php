<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraceLog extends Model
{
    use HasFactory, UuidTrait;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const CHANNEL_WA = 'wa';

    public const CHANNEL_EMAIL = 'email';

    protected $table = 'grace_logs';

    protected $guarded = [];

    protected $casts = [
        'grace_started_at' => 'date',
        'sent_at' => 'datetime',
    ];

    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markFailed(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $reason,
        ]);
    }
}

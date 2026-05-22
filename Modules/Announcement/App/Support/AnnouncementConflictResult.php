<?php

namespace Modules\Announcement\App\Support;

class AnnouncementConflictResult
{
    public function __construct(
        public string $status,
        public array $targets,
        public ?string $conflictReason = null,
    ) {
    }
}

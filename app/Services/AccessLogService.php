<?php

namespace App\Services;

use App\Models\AccessLog;

class AccessLogService
{
    public function create(array $data): AccessLog
    {
        return AccessLog::updateOrCreate(
            [
                'progress' => $data['progress'],
                'email' => $data['email'] ?? null,
                'company_id' => $data['company_id'] ?? null,
            ],
            $data
        );
    }
}

<?php

namespace Modules\Logs\App\Services;

use Modules\Logs\App\Models\Log;

class LogService
{
    public static function create($request)
    {
        $data = Log::create([
            'fid' => $request['fid'],
            'category' => $request['category'],
            'title' => $request['title'],
            'note' => $request['note'],
            'company_id' => $request['company_id'],
        ]);

        return $data;
    }
}

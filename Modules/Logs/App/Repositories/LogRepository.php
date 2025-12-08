<?php

namespace Modules\Logs\App\Repositories;

use Modules\Logs\App\Models\Log;

class LogRepository
{
    public function create($request)
    {
        $data = Log::create([
            'fid' => $request['fid'],
            'category' => $request['category'],
            'note' => $request['note'],
            'company_id' => $request['company_id'],
        ]);

        return $data;
    }
}

<?php

namespace Modules\DeveloperAccess\App\Repositories;

use Illuminate\Support\Str;
use Modules\Contact\App\Models\Contact;
use Modules\DeveloperAccess\App\Models\DeveloperAccess;

class DeveloperAccessRepository
{

    public function create($request)
    {
        $data = DeveloperAccess::create([
            'account_name' => $request['account_name'],
            'account_email' => $request['account_email'],
            'token_access' => $request['token_access'],
            'time_access' => $request['time_access'],
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
            'note' => $request['note'],
            'company_id' => $request['company_id'],
            'company_name' => $request['company_name'],
        ]);

        return $data;
    }

    public function destroyBulkByToken($request)
    {
        $data = DeveloperAccess::whereIn("token_access", $request['tokens'])->delete();
        return $data;
    }
}

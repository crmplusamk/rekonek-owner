<?php

namespace Modules\Deleted\App\Repositories;

use Illuminate\Support\Str;
use Modules\Deleted\App\Models\DeletedCompany;

class DeletedCompanyRepository
{

    public function create($request)
    {
        $data = DeletedCompany::create([
            'contact_id' => $request['contact_id'],
            'company_id' => $request['company_id'],
            'company_name' => $request['company_name'],
            'name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'is_status' => $request['is_status'],
            'reason' => $request['reason'],
            'note' => $request['note'],
            'request_date' => $request['request_date'],
            'deleted_date' => $request['deleted_date'],
            'deleted_by' => $request['deleted_by'],
            'metadata' => $request['metadata'],
        ]);

        return $data;
    }

    public function check($companyId)
    {
        $data = DeletedCompany::where([
            "company_id" => $companyId,
            "is_status" => 0
        ])->exists();

        return $data;
    }
}

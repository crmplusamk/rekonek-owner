<?php

namespace Modules\Client\App\Services;

use Illuminate\Support\Facades\Http;

class CompanyService
{

    public function generate($customer)
    {
        $response = Http::acceptJson()->post(env('CRM_CLIENT_HOST')."/api/create-company", $customer);

        if (!$response->successful()) {
            return [
                "error" => true,
                "message" => $response->json()['message']
            ];
        }

        return [
            "success" => true,
            "message" => "Successfully generate company"
        ];
    }

}

<?php

namespace Modules\Subscription\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

class SubscriptionService
{

    public function updatePackage($data)
    {
        $data = SubscriptionPackage::updateOrCreate(
            [
                'customer_id' => $data['customer_id'],
                'company_id' => $data['company_id'],
            ],
            [
                'code' => Str::upper(Str::random(5)),
                'customer_id' => $data['customer_id'],
                'company_id' => $data['company_id'],
                'package_id' => $data['package_id'],
                'termin_duration' => $data['termin_duration'],
                'termin' => $data['termin'],
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]
        );

        $data->load('package');
        return $data;
    }

    public function updateAddon($data)
    {
        $existAddon = SubscriptionAddon::where([
            'customer_id' => $data['customer_id'],
            'company_id' => $data['company_id'],
            'addon_id' => $data['addon_id']
        ])->first();

        if (!$existAddon) {

            $existAddon = SubscriptionAddon::create([
                'code' => Str::upper(Str::random(5)),
                'customer_id' => $data['customer_id'],
                'company_id' => $data['company_id'],
                'addon_id' => $data['addon_id'],
                'charge' => $data['charge'],
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);

        } else {

            $existAddon->update([
                'charge' => $data['charge'] + $data['additional_charge'] ,
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);
        }

        return $existAddon;
    }
}

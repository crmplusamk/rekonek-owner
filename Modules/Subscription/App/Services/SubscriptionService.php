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
        $data = SubscriptionPackage::create([
            'code' => Str::upper(Str::random(5)),
            'customer_id' => $data['customer_id'],
            'company_id' => $data['company_id'],
            'package_id' => $data['package_id'],
            'termin_duration' => $data['termin_duration'],
            'termin' => $data['termin'],
            'started_at' => $data['started_at'],
            'expired_at' => $data['expired_at'],
            'is_active' => true,
            'is_trial' => $data['is_trial'] ?? 'trial',
            'is_grace' => $data['is_grace'] ?? 'active',
            'grace_started_at' => $data['grace_started_at'] ?? null,
        ]);

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
            // Accumulate charge with existing addon charge
            $existAddon->update([
                'charge' => $existAddon->charge + $data['charge'],
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);
        }

        return $existAddon;
    }
}

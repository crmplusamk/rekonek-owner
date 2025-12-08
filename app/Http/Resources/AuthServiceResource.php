<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscription\App\resources\SubscriptionAddonResource;

class AuthServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subscription' => [
                'id' => $this['subsPackage']->id,
                'start_date' => $this['subsPackage']->started_at,
                'end_date' => $this['subsPackage']->expired_at,
                'is_active' => $this['subsPackage']->is_active,
                'grece_day_ended_at' => $this['subsPackage']->grece_day_ended_at,
                'termin_duration' => $this['subsPackage']->termin_duration,
                'termin' => $this['subsPackage']->termin,
                'package' => new PackageResource($this['subsPackage']->package),
                'addon' => SubscriptionAddonResource::collection($this['subsAddon']),
            ],
            'features' => AuthFeatureResource::collection($this['subsPackage']->package->features),
        ];
    }
}

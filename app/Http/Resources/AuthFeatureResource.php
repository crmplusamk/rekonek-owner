<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthFeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "feature_name" => $this->name,
            "feature_key" => $this->key,
            "visiblity" => $this->pivot->visiblity,
            "included" => $this->pivot->included,
            "limit" => $this->pivot->limit == null || !is_numeric($this->pivot->limit)
                ? null
                : $this->pivot->limit + ($this->addon?->subscriptionAddons[0]?->charge ?? 0),
            "limit_type" => $this->pivot->limit_type,
            "has_addon" => isset($this->addon->subscriptionAddons[0]) ? true : false
        ];
    }
}

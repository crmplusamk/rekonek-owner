<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturePackageListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "key" => $this->key,
            "limit" => $this->pivot->limit,
            "limit_type" => $this->pivot->limit_type,
            "included" => $this->pivot->included,
        ];
    }
}

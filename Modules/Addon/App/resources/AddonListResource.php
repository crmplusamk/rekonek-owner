<?php

namespace Modules\Addon\App\resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddonListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "charge" => $this->charge,
            "price" => $this->price,
        ];
    }
}

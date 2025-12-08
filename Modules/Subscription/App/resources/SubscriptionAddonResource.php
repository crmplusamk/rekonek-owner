<?php

namespace Modules\Subscription\App\resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionAddonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        $sdate = Carbon::parse($this->started_at);
        $edate = Carbon::parse($this->expired_at);
        $diff = $sdate->diffInDays($edate);

        return [
            'id' => $this->id,
            'addon_id' => $this->addon->id,
            'name' => $this->addon->name,
            'key' => $this->addon->feature->key,
            'charge' => $this->charge,
            'price' => $this->addon->price,
            'start_date' => $this->started_at,
            'end_date' => $this->expired_at,
            'days' => $diff,
            'total' => $this->charge * $diff * $this->addon->price,
            'is_active' => $this->is_active,
        ];
    }
}

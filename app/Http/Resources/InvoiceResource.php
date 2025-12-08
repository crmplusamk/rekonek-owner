<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            "code" => $this->code,
            "date" => $this->date,
            "due_date" => $this->due_date,
            "discount" => $this->discount,
            "total" => $this->total,
            "status" => $this->status,
            "customer" => new ContactListResource($this->customer),
            "subscription" => [
                "id" => $this->subscription->id,
                "start_date" => $this->subscription->start_date,
                "end_date" => $this->subscription->end_date,
                "is_active" => $this->subscription->is_active,
                "package" => new PackageListResource($this->subscription->package),
                "features" => SubscriptionFeatureListResource::collection($this->subscription->features)
            ],
            "invoice_items" => InvoiceItemListResource::collection($this->items)
        ];
    }
}

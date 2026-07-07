<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthFeatureResource extends JsonResource
{
    /**
     * Kunci fitur AI Credit — addon-nya bersifat prepaid (saldo sekali-bayar yang carry-over
     * lintas cycle & perpanjangan), BUKAN recurring per cycle. Karena itu charge-nya TIDAK
     * dilipat ke cycle limit; saldo addon dikirim terpisah (addon_credit) agar rekonek
     * mengelolanya sebagai bucket sendiri. Fitur lain tetap memakai fold existing.
     */
    private const AI_CREDIT_KEY = 'AICRD';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subsAddon = $this->addon?->subscriptionAddons[0] ?? null;
        $isAiCredit = $this->key === self::AI_CREDIT_KEY;

        $limitNumeric = $this->pivot->limit != null && is_numeric($this->pivot->limit);

        // Fitur non-AICRD: limit = pivot.limit + charge addon (fold recurring existing).
        // AICRD: limit = pivot.limit saja (cycle allocation), addon dikirim di addon_credit.
        $limit = ! $limitNumeric
            ? null
            : ($isAiCredit
                ? (int) $this->pivot->limit
                : (int) $this->pivot->limit + ($subsAddon?->charge ?? 0));

        $data = [
            "feature_name" => $this->name,
            "feature_key" => $this->key,
            "visiblity" => $this->pivot->visiblity,
            "included" => $this->pivot->included,
            "limit" => $limit,
            "limit_type" => $this->pivot->limit_type,
            "has_addon" => $subsAddon ? true : false,
        ];

        if ($isAiCredit) {
            // Saldo addon hanya valid selama addon aktif & belum expired (hangus saat lapse penuh).
            $addonValid = $subsAddon
                && $subsAddon->is_active
                && $subsAddon->expired_at
                && Carbon::parse($subsAddon->expired_at)->endOfDay()->gte(Carbon::now());

            $data["addon_credit"] = $addonValid ? (int) $subsAddon->charge : 0;
            // Anchor window usage addon di rekonek (SUM addon_credits_used sejak started_at ini).
            $data["addon_started_at"] = $addonValid && $subsAddon->started_at
                ? Carbon::parse($subsAddon->started_at)->toIso8601String()
                : null;
        }

        return $data;
    }
}

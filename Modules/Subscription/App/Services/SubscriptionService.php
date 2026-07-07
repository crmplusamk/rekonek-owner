<?php

namespace Modules\Subscription\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Addon\App\Models\Addon;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

class SubscriptionService
{
    /** Fitur AI Credit — addon prepaid (saldo hangus saat lapse penuh, tidak akumulasi lintas lapse). */
    private const AI_CREDIT_KEY = 'AICRD';


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
            // AI Credit (prepaid): jika addon sebelumnya sudah lapse PENUH (expired melewati jendela
            // grace), saldo lama hangus — perlakukan pembelian ini sebagai saldo baru (RESET charge &
            // started_at), bukan akumulasi. Selama masih kontinu (belum expired atau masih dalam
            // grace), tetap akumulasi seperti addon lain. Ambang selaras extendAiCreditAddonExpiry.
            $graceDays = \App\Services\GracePeriod\GraceLifecycleService::GRACE_DURATION_DAYS;
            $lapsed = $this->isAiCredit($data['addon_id'])
                && $existAddon->expired_at
                && Carbon::parse($existAddon->expired_at)->endOfDay()->lt(Carbon::now()->subDays($graceDays));

            $existAddon->update([
                'charge' => $lapsed ? $data['charge'] : $existAddon->charge + $data['charge'],
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);
        }

        return $existAddon;
    }

    /**
     * Perpanjang expired_at addon AI Credit aktif milik company ke akhir cycle langganan yang baru,
     * agar saldo prepaid tetap valid (carry-over) di cycle berikutnya. Dipanggil saat settlement
     * paket (new/renew) yang membuat cycle baru — bila tanpa ini, addon akan expired di tengah
     * cycle baru dan saldo hilang meski user memperpanjang.
     *
     * HANYA memperpanjang addon yang masih kontinu: belum expired, ATAU expired tetapi masih dalam
     * jendela grace (renewal saat grace). Addon yang sudah lapse penuh (expired > grace) TIDAK
     * diperpanjang → saldo hangus dan tidak "resurrect" saat company berlangganan lagi dari nol.
     */
    public function extendAiCreditAddonExpiry(string $companyId, $expiredAt): void
    {
        $aiCreditAddonIds = Addon::whereHas('feature', function ($q) {
            $q->where('key', self::AI_CREDIT_KEY);
        })->pluck('id');

        if ($aiCreditAddonIds->isEmpty()) {
            return;
        }

        $graceDays = \App\Services\GracePeriod\GraceLifecycleService::GRACE_DURATION_DAYS;
        $continuityFloor = Carbon::now()->subDays($graceDays);

        SubscriptionAddon::where('company_id', $companyId)
            ->whereIn('addon_id', $aiCreditAddonIds)
            ->where('is_active', true)
            ->where('expired_at', '>=', $continuityFloor)
            ->update(['expired_at' => $expiredAt]);
    }

    private function isAiCredit($addonId): bool
    {
        return Addon::where('id', $addonId)
            ->whereHas('feature', function ($q) {
                $q->where('key', self::AI_CREDIT_KEY);
            })
            ->exists();
    }
}

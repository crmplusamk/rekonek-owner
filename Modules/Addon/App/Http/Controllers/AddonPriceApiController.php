<?php

namespace Modules\Addon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\Services\AddonTierPricingService;
use Modules\Package\App\Services\PricingService;

/**
 * Quote harga addon (untuk ditampilkan app) — sumber kebenaran = PricingService.
 * Mengembalikan harga normal vs diskon (tier) agar UI bisa tampil harga coret + badge "Diskon".
 */
class AddonPriceApiController extends Controller
{
    public function __construct(
        private PricingService $pricing,
        private AddonTierPricingService $tierPricing,
    ) {
    }

    /**
     * GET /api/v1/addon-price?addon_id=&qty=&termin=&company_id=
     */
    public function price(Request $request)
    {
        try {
            $addon = Addon::find($request->query('addon_id'));
            if (! $addon) {
                return response()->json(['success' => false, 'code' => 'ADDON_NOT_FOUND', 'message' => 'Addon tidak ditemukan'], 404);
            }

            $qty = max(1, (int) $request->query('qty', 1));
            $termin = $request->query('termin', 'month');
            $companyId = $request->query('company_id');

            $unit = $this->pricing->addonUnitPrice($addon, $termin, $qty, $companyId);
            $normalUnit = $this->pricing->addonNormalUnitPrice($addon, $termin, $companyId);

            $subtotal = (int) round($unit * $qty);
            $normalSubtotal = (int) round($normalUnit * $qty);
            $tier = $this->tierPricing->resolveTier((string) $addon->id, $qty);

            return response()->json([
                'success' => true,
                'data' => [
                    'addon_id' => $addon->id,
                    'quantity' => $qty,
                    'unit_price' => (int) round($unit),
                    'subtotal' => $subtotal,
                    'normal_unit_price' => (int) round($normalUnit),
                    'normal_subtotal' => $normalSubtotal,
                    'has_discount' => $subtotal < $normalSubtotal,
                    'discount_label' => $tier->label ?? null,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'code' => 'INTERNAL_ERROR', 'message' => $th->getMessage()], 500);
        }
    }
}

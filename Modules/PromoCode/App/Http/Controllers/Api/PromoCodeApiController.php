<?php

namespace Modules\PromoCode\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;

class PromoCodeApiController extends Controller
{
    /**
     * Check promo code availability and validate
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|uuid',
            'company_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (! $promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak ditemukan',
            ], 404);
        }

        if (! $promoCode->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak tersedia atau sudah kadaluarsa',
            ], 400);
        }

        if (! $promoCode->canBeUsedBy($request->customer_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan promo code ini',
            ], 400);
        }

        $discount = null;
        if ($request->amount !== null) {
            if ($promoCode->min_purchase && $request->amount < $promoCode->min_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal pembelian untuk promo code ini adalah '.number_format($promoCode->min_purchase, 0, ',', '.'),
                ], 400);
            }
            $discount = $promoCode->calculateDiscount($request->amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode promo valid',
            'data' => [
                'code' => $promoCode->code,
                'name' => $promoCode->name,
                'discount_type' => $promoCode->discount_type,
                'discount_percentage' => $promoCode->discount_percentage,
                'discount_amount' => $promoCode->discount_amount,
                'min_purchase' => $promoCode->min_purchase,
                'max_discount' => $promoCode->max_discount,
                'discount' => $discount,
                'description' => $promoCode->description,
            ],
        ], 200);
    }

    /**
     * Calculate discount for given amount
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (! $promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak ditemukan',
            ], 404);
        }

        $discount = $promoCode->calculateDiscount($request->amount);
        $finalAmount = $request->amount - $discount;

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $request->amount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'discount_type' => $promoCode->discount_type,
                'discount_percentage' => $promoCode->discount_percentage,
                'discount_amount' => $promoCode->discount_amount,
            ],
        ], 200);
    }

    /**
     * Get available promo codes
     */
    public function available(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $promoCodes = PromoCode::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promoCodes->map(function ($promoCode) {
                return [
                    'code' => $promoCode->code,
                    'name' => $promoCode->name,
                    'discount_type' => $promoCode->discount_type,
                    'discount_percentage' => $promoCode->discount_percentage,
                    'discount_amount' => $promoCode->discount_amount,
                    'min_purchase' => $promoCode->min_purchase,
                    'max_discount' => $promoCode->max_discount,
                    'description' => $promoCode->description,
                    'start_date' => $promoCode->start_date,
                    'end_date' => $promoCode->end_date,
                ];
            }),
        ], 200);
    }

    /**
     * Record promo code usage
     */
    public function use(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'customer_id' => 'nullable|uuid',
            'company_id' => 'nullable|uuid',
            'contact_id' => 'nullable|string',
            'purchase_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (! $promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak ditemukan',
            ], 404);
        }

        if (! $promoCode->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak tersedia',
            ], 400);
        }

        if (! $promoCode->canBeUsedBy($request->customer_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan promo code ini',
            ], 400);
        }

        $discountAmount = $request->discount_amount;
        if ($discountAmount === null && $request->purchase_amount !== null) {
            $discountAmount = $promoCode->calculateDiscount($request->purchase_amount);
        }

        $usage = PromoCodeUsage::create([
            'promo_code_id' => $promoCode->id,
            'customer_id' => $request->customer_id,
            'company_id' => $request->company_id,
            'contact_id' => $request->contact_id,
            'purchase_amount' => $request->purchase_amount,
            'discount_amount' => $discountAmount,
            'metadata' => $request->metadata,
        ]);

        $promoCode->increment('used_count');

        return response()->json([
            'success' => true,
            'message' => 'Promo code berhasil digunakan',
            'data' => [
                'usage_id' => $usage->id,
                'discount_amount' => $usage->discount_amount,
            ],
        ], 200);
    }
}

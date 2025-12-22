<?php

namespace Modules\Referral\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Referral\App\Models\Referral;
use Modules\Referral\App\Models\ReferralUsage;

class ReferralApiController extends Controller
{
    /**
     * Check referral code availability and validate
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

        $referral = Referral::where('code', $request->code)->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak ditemukan',
            ], 404);
        }

        if (!$referral->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak tersedia atau sudah kadaluarsa',
            ], 400);
        }

        if (!$referral->canBeUsedBy($request->customer_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan referral code ini',
            ], 400);
        }

        $discount = null;
        if ($request->amount !== null) {
            if ($referral->min_purchase && $request->amount < $referral->min_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal pembelian untuk referral code ini adalah ' . number_format($referral->min_purchase, 0, ',', '.'),
                ], 400);
            }
            $discount = $referral->calculateDiscount($request->amount);
    }

        return response()->json([
            'success' => true,
            'message' => 'Kode referral valid',
            'data' => [
                'code' => $referral->code,
                'name' => $referral->name,
                'discount_type' => $referral->discount_type,
                'discount_percentage' => $referral->discount_percentage,
                'discount_amount' => $referral->discount_amount,
                'min_purchase' => $referral->min_purchase,
                'max_discount' => $referral->max_discount,
                'discount' => $discount,
                'description' => $referral->description,
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

        $referral = Referral::where('code', $request->code)->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak ditemukan',
            ], 404);
    }

        $discount = $referral->calculateDiscount($request->amount);
        $finalAmount = $request->amount - $discount;

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $request->amount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'discount_type' => $referral->discount_type,
                'discount_percentage' => $referral->discount_percentage,
                'discount_amount' => $referral->discount_amount,
            ],
        ], 200);
    }

    /**
     * Get available referral codes
     */
    public function available(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $referrals = Referral::where('is_active', true)
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
            'data' => $referrals->map(function ($referral) {
                return [
                    'code' => $referral->code,
                    'name' => $referral->name,
                    'discount_type' => $referral->discount_type,
                    'discount_percentage' => $referral->discount_percentage,
                    'discount_amount' => $referral->discount_amount,
                    'min_purchase' => $referral->min_purchase,
                    'max_discount' => $referral->max_discount,
                    'description' => $referral->description,
                    'start_date' => $referral->start_date,
                    'end_date' => $referral->end_date,
                ];
            }),
        ], 200);
    }

    /**
     * Record referral code usage
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

        $referral = Referral::where('code', $request->code)->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak ditemukan',
            ], 404);
    }

        if (!$referral->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak tersedia',
            ], 400);
        }

        if (!$referral->canBeUsedBy($request->customer_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan referral code ini',
            ], 400);
        }

        $discountAmount = $request->discount_amount;
        if ($discountAmount === null && $request->purchase_amount !== null) {
            $discountAmount = $referral->calculateDiscount($request->purchase_amount);
        }

        $usage = ReferralUsage::create([
            'referral_id' => $referral->id,
            'customer_id' => $request->customer_id,
            'company_id' => $request->company_id,
            'contact_id' => $request->contact_id,
            'purchase_amount' => $request->purchase_amount,
            'discount_amount' => $discountAmount,
            'metadata' => $request->metadata,
        ]);

        $referral->increment('used_count');

        return response()->json([
            'success' => true,
            'message' => 'Referral code berhasil digunakan',
            'data' => [
                'usage_id' => $usage->id,
                'discount_amount' => $usage->discount_amount,
            ],
        ], 200);
    }
}

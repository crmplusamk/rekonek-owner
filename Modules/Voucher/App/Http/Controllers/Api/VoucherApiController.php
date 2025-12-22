<?php

namespace Modules\Voucher\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Voucher\App\Models\Voucher;
use Modules\Voucher\App\Models\VoucherUsage;

class VoucherApiController extends Controller
{
    /**
     * Check voucher availability and validate
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|uuid',
            'company_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak ditemukan',
            ], 404);
        }

        if (!$voucher->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak tersedia atau sudah kadaluarsa',
            ], 400);
        }

        if (!$voucher->canBeUsedBy($request->user_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan voucher ini',
            ], 400);
        }

        $discount = null;
        if ($request->amount !== null) {
            if ($voucher->min_purchase && $request->amount < $voucher->min_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal pembelian untuk voucher ini adalah ' . number_format($voucher->min_purchase, 0, ',', '.'),
                ], 400);
            }
            $discount = $voucher->calculateDiscount($request->amount);
    }

        return response()->json([
            'success' => true,
            'message' => 'Kode voucher valid',
            'data' => [
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_type' => $voucher->discount_type,
                'discount_percentage' => $voucher->discount_percentage,
                'discount_amount' => $voucher->discount_amount,
                'min_purchase' => $voucher->min_purchase,
                'max_discount' => $voucher->max_discount,
                'discount' => $discount,
                'description' => $voucher->description,
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

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak ditemukan',
            ], 404);
    }

        $discount = $voucher->calculateDiscount($request->amount);
        $finalAmount = $request->amount - $discount;

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $request->amount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'discount_type' => $voucher->discount_type,
                'discount_percentage' => $voucher->discount_percentage,
                'discount_amount' => $voucher->discount_amount,
            ],
        ], 200);
    }

    /**
     * Get available vouchers
     */
    public function available(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $vouchers = Voucher::where('is_active', true)
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
            'data' => $vouchers->map(function ($voucher) {
                return [
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'discount_type' => $voucher->discount_type,
                    'discount_percentage' => $voucher->discount_percentage,
                    'discount_amount' => $voucher->discount_amount,
                    'min_purchase' => $voucher->min_purchase,
                    'max_discount' => $voucher->max_discount,
                    'description' => $voucher->description,
                    'start_date' => $voucher->start_date,
                    'end_date' => $voucher->end_date,
                ];
            }),
        ], 200);
    }

    /**
     * Record voucher usage
     */
    public function use(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'user_id' => 'nullable|uuid',
            'company_id' => 'nullable|uuid',
            'order_id' => 'nullable|string',
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

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak ditemukan',
            ], 404);
        }

        if (!$voucher->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak tersedia',
            ], 400);
        }

        if (!$voucher->canBeUsedBy($request->user_id, $request->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda telah mencapai batas penggunaan voucher ini',
            ], 400);
        }

        $discountAmount = $request->discount_amount;
        if ($discountAmount === null && $request->purchase_amount !== null) {
            $discountAmount = $voucher->calculateDiscount($request->purchase_amount);
        }

        $usage = VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'user_id' => $request->user_id,
            'company_id' => $request->company_id,
            'order_id' => $request->order_id,
            'purchase_amount' => $request->purchase_amount,
            'discount_amount' => $discountAmount,
            'metadata' => $request->metadata,
        ]);

        $voucher->increment('used_count');

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil digunakan',
            'data' => [
                'usage_id' => $usage->id,
                'discount_amount' => $usage->discount_amount,
            ],
        ], 200);
    }
}

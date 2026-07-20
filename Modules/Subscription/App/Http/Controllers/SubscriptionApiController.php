<?php

namespace Modules\Subscription\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\App\Services\RenewalQuoteService;

/**
 * API internal (dikonsumsi rekonek-app).
 */
class SubscriptionApiController extends Controller
{
    public function __construct(private RenewalQuoteService $quoteService)
    {
    }

    /**
     * GET /api/v1/subscription/{companyId}/renewal-quote
     * Proyeksi tagihan perpanjangan berikutnya — sumber kebenaran "Nilai Langganan" di app.
     */
    public function renewalQuote(string $companyId)
    {
        try {
            $quote = $this->quoteService->quoteForCompany($companyId);

            if (! $quote) {
                return response()->json([
                    'success' => false,
                    'code' => 'SUBSCRIPTION_NOT_FOUND',
                    'message' => 'Langganan aktif tidak ditemukan.',
                ], 404);
            }

            // `items` internal (dipakai job) tidak diekspos ke API.
            unset($quote['items']);

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $quote,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('renewalQuote gagal', ['company_id' => $companyId, 'error' => $th->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'INTERNAL_ERROR',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}

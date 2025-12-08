<?php

namespace Modules\Logs\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Invoices\App\Models\Invoice;
use Modules\Logs\App\Models\Log;

class LogsApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function logSubData(Request $request)
    {
        try {

            $logs = Log::where([
                    'company_id' => $request->company_id,
                    'category' => 'subscription'
                ])
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(8)
                ->groupBy(function ($item) {
                    return $item->created_at->format('d M Y');
                });

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 200);
        }
    }

    public function logOrderData(Request $request)
    {
        try {

            $invoice = Invoice::with('payments')->find($request->invoice_id);
            $ids = $invoice->payments->pluck('id')->toArray();
            $ids[] = $request->invoice_id;

            $logs = Log::whereIn('fid', $ids)
                ->where('category', 'order')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(8)
                ->groupBy(function ($item) {
                    return $item->created_at->format('d M Y');
                });

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 200);
        }
    }
}

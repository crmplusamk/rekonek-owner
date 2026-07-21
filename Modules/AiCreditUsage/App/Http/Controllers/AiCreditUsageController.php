<?php

namespace Modules\AiCreditUsage\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AiCreditUsage\App\Services\AiCreditUsageReportService;

class AiCreditUsageController extends Controller
{
    public $reportService;

    public function __construct(AiCreditUsageReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the AI credit usage report page (shell + filter + JS).
     */
    public function index()
    {
        return view('aicreditusage::index');
    }

    /**
     * Overall summary (all companies) for the selected range: totals, feature split, daily trend.
     */
    public function summary(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolveRange($request);

        return response()->json([
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => $this->reportService->summary($start, $end),
            'features' => $this->reportService->featureBreakdown($start, $end),
            'trend' => $this->reportService->dailyTrend($start, $end),
        ]);
    }

    /**
     * Per-company usage breakdown for the selected range (DataTable source).
     */
    public function datatable(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);

        $rows = $this->reportService->perCompany($start, $end, $request->input('search') ?: null);

        return datatables()->collection($rows)->make(true);
    }

    /**
     * Per-organisation detail page (drill-down from the per-company table).
     */
    public function show(string $company)
    {
        return view('aicreditusage::show', [
            'companyId' => $company,
            'companyName' => $this->reportService->companyName($company) ?: '—',
        ]);
    }

    /**
     * Summary (totals, feature split, daily trend) scoped to one company.
     */
    public function companySummary(Request $request, string $company): JsonResponse
    {
        [$start, $end] = $this->resolveRange($request);

        return response()->json([
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => $this->reportService->summary($start, $end, $company),
            'features' => $this->reportService->featureBreakdown($start, $end, $company),
            'trend' => $this->reportService->dailyTrend($start, $end, $company),
        ]);
    }

    /**
     * Individual response rows for one company (DataTable source, server-side).
     */
    public function companyDatatable(Request $request, string $company)
    {
        [$start, $end] = $this->resolveRange($request);

        $query = $this->reportService->responses($start, $end, $company);

        return datatables()->query($query)
            ->orderColumn('total_tokens', '(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) $1')
            ->make(true);
    }

    /**
     * Reset AI credit organisasi: kembalikan pemakaian cycle berjalan ke 0 dengan menulis satu
     * entri offset (history pemakaian tetap utuh; reset tercatat sebagai audit). Pool AICRD sama.
     */
    public function reset(Request $request, string $company)
    {
        [$start, $end] = $this->reportService->currentCycleWindow($company);
        $used = $this->reportService->cycleCreditsUsed($company, $start, $end);

        if ($used === 0) {
            notify()->info('Tidak ada credit terpakai pada cycle berjalan — tidak ada yang perlu direset.');

            return back();
        }

        $this->reportService->recordAdjustment(
            $company,
            -$used, // offset → SUM cycle = 0 → sisa penuh
            'admin_reset',
            'Reset AI credit oleh admin owner.',
            optional(Auth::user())->email ?? 'owner-admin'
        );

        notify()->success("AI credit organisasi direset. {$used} credit terpakai dikompensasi (history tetap tersimpan).");

        return back();
    }

    /**
     * Penyesuaian manual: grant (tambah sisa) atau deduct (kurangi sisa) sejumlah credit, dengan
     * alasan. Ditulis sebagai entri audit di ai_credit_usages.
     */
    public function adjust(Request $request, string $company)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'direction' => ['required', 'in:grant,deduct'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // grant = menambah sisa = mengurangi usage (negatif); deduct = menambah usage (positif).
        $signed = $validated['direction'] === 'grant'
            ? -$validated['amount']
            : $validated['amount'];

        $this->reportService->recordAdjustment(
            $company,
            $signed,
            'admin_adjustment',
            $validated['reason'] ?: ($validated['direction'] === 'grant' ? 'Grant credit manual.' : 'Deduct credit manual.'),
            optional(Auth::user())->email ?? 'owner-admin'
        );

        $verb = $validated['direction'] === 'grant' ? 'ditambah' : 'dikurangi';
        notify()->success("AI credit organisasi {$verb} {$validated['amount']} credit.");

        return back();
    }

    /**
     * Resolve [start, end] Carbon bounds from the request. Defaults to the last 30 days,
     * tolerates malformed input, normalises reversed ranges, and caps the window at 366 days
     * to protect the aggregate scan + trend gap-fill loop.
     */
    private function resolveRange(Request $request): array
    {
        $start = $this->parseDate($request->input('start_date'), Carbon::now()->subDays(29))->startOfDay();
        $end = $this->parseDate($request->input('end_date'), Carbon::now())->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366)->startOfDay();
        }

        return [$start, $end];
    }

    private function parseDate(?string $value, Carbon $default): Carbon
    {
        if (! $value) {
            return $default->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return $default->copy();
        }
    }
}

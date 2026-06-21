<?php

namespace Modules\AiCreditUsage\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AiCreditUsage\App\Repositories\AiCreditUsageReportRepository;

class AiCreditUsageController extends Controller
{
    public $reportRepo;

    public function __construct(AiCreditUsageReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
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
            'summary' => $this->reportRepo->summary($start, $end),
            'features' => $this->reportRepo->featureBreakdown($start, $end),
            'trend' => $this->reportRepo->dailyTrend($start, $end),
        ]);
    }

    /**
     * Per-company usage breakdown for the selected range (DataTable source).
     */
    public function datatable(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);

        $rows = $this->reportRepo->perCompany($start, $end, $request->input('search') ?: null);

        return datatables()->collection($rows)->make(true);
    }

    /**
     * Per-organisation detail page (drill-down from the per-company table).
     */
    public function show(string $company)
    {
        return view('aicreditusage::show', [
            'companyId' => $company,
            'companyName' => $this->reportRepo->companyName($company) ?: '—',
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
            'summary' => $this->reportRepo->summary($start, $end, $company),
            'features' => $this->reportRepo->featureBreakdown($start, $end, $company),
            'trend' => $this->reportRepo->dailyTrend($start, $end, $company),
        ]);
    }

    /**
     * Individual response rows for one company (DataTable source, server-side).
     */
    public function companyDatatable(Request $request, string $company)
    {
        [$start, $end] = $this->resolveRange($request);

        $query = $this->reportRepo->responses($start, $end, $company);

        return datatables()->query($query)
            ->orderColumn('total_tokens', '(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) $1')
            ->make(true);
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

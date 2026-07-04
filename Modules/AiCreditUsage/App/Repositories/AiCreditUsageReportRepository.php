<?php

namespace Modules\AiCreditUsage\App\Repositories;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Read-only reporting layer over the main rekonek app's `ai_credit_usages` table
 * (single source of truth for AI credit consumption — auto-reply + live testing),
 * reached through the existing `client` DB connection.
 *
 * The overall (all-company) and per-company (drill-down) reports share the same
 * aggregation methods — pass `$companyId` to scope them to one organisation.
 * All metrics are raw usage (tokens + credits).
 */
class AiCreditUsageReportRepository
{
    private const CONNECTION = 'client';

    private const TABLE = 'ai_credit_usages';

    /**
     * Overall totals for the window [start, end], optionally scoped to one company.
     */
    public function summary(CarbonInterface $start, CarbonInterface $end, ?string $companyId = null): object
    {
        $row = DB::connection(self::CONNECTION)
            ->table(self::TABLE)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                COUNT(*) AS event_count,
                COUNT(DISTINCT company_id) AS active_companies,
                COALESCE(SUM(input_tokens), 0) AS total_input_tokens,
                COALESCE(SUM(output_tokens), 0) AS total_output_tokens,
                COALESCE(SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)), 0) AS total_tokens,
                COALESCE(SUM(credits_used), 0) AS total_credits
            ')
            ->first();

        return (object) [
            'event_count' => (int) ($row->event_count ?? 0),
            'active_companies' => (int) ($row->active_companies ?? 0),
            'total_input_tokens' => (int) ($row->total_input_tokens ?? 0),
            'total_output_tokens' => (int) ($row->total_output_tokens ?? 0),
            'total_tokens' => (int) ($row->total_tokens ?? 0),
            'total_credits' => (int) ($row->total_credits ?? 0),
        ];
    }

    /**
     * Credit & event split per feature (chat_auto_reply vs agent_testing_sandbox),
     * optionally scoped to one company.
     */
    public function featureBreakdown(CarbonInterface $start, CarbonInterface $end, ?string $companyId = null): Collection
    {
        return DB::connection(self::CONNECTION)
            ->table(self::TABLE)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('feature')
            ->selectRaw('
                feature,
                COUNT(*) AS event_count,
                COALESCE(SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)), 0) AS total_tokens,
                COALESCE(SUM(credits_used), 0) AS total_credits
            ')
            ->orderByDesc('total_credits')
            ->get()
            ->map(fn ($r) => (object) [
                'feature' => $r->feature,
                'event_count' => (int) $r->event_count,
                'total_tokens' => (int) $r->total_tokens,
                'total_credits' => (int) $r->total_credits,
            ]);
    }

    /**
     * Daily credit/token series for [start, end] (gaps filled with zero),
     * optionally scoped to one company.
     */
    public function dailyTrend(CarbonInterface $start, CarbonInterface $end, ?string $companyId = null): array
    {
        $rows = DB::connection(self::CONNECTION)
            ->table(self::TABLE)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                DATE(created_at) AS date,
                COALESCE(SUM(credits_used), 0) AS credits,
                COALESCE(SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)), 0) AS tokens
            ')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->keyBy(fn ($r) => (string) $r->date);

        $series = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->format('Y-m-d');
            $row = $rows->get($key);
            $series[] = [
                'date' => $key,
                'credits' => $row ? (int) $row->credits : 0,
                'tokens' => $row ? (int) $row->tokens : 0,
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Per-company usage breakdown for [start, end] (one row per company).
     * Optionally filtered by a company-name search term.
     */
    public function perCompany(CarbonInterface $start, CarbonInterface $end, ?string $search = null): Collection
    {
        return DB::connection(self::CONNECTION)
            ->table(self::TABLE.' as u')
            ->leftJoin('companies as c', 'c.id', '=', 'u.company_id')
            ->whereBetween('u.created_at', [$start, $end])
            ->when($search, function ($query) use ($search) {
                $query->where('c.name', 'ilike', '%'.$search.'%');
            })
            ->groupBy('u.company_id', 'c.name')
            ->selectRaw('
                u.company_id,
                c.name AS company_name,
                COUNT(*) AS event_count,
                COALESCE(SUM(u.input_tokens), 0) AS input_tokens,
                COALESCE(SUM(u.output_tokens), 0) AS output_tokens,
                COALESCE(SUM(COALESCE(u.input_tokens, 0) + COALESCE(u.output_tokens, 0)), 0) AS total_tokens,
                COALESCE(SUM(u.credits_used), 0) AS credits_used
            ')
            ->get()
            ->map(fn ($r) => (object) [
                'company_id' => $r->company_id,
                'company_name' => $r->company_name ?: '—',
                'event_count' => (int) $r->event_count,
                'input_tokens' => (int) $r->input_tokens,
                'output_tokens' => (int) $r->output_tokens,
                'total_tokens' => (int) $r->total_tokens,
                'credits_used' => (int) $r->credits_used,
            ]);
    }

    /**
     * Individual usage rows (one per AI response) for a company in [start, end].
     * Returns the query builder so the DataTable (yajra query engine) can paginate
     * and sort server-side. `total_tokens` is computed for display + sorting.
     */
    public function responses(CarbonInterface $start, CarbonInterface $end, string $companyId): Builder
    {
        return DB::connection(self::CONNECTION)
            ->table(self::TABLE)
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                id,
                created_at,
                feature,
                COALESCE(input_tokens, 0) AS input_tokens,
                COALESCE(output_tokens, 0) AS output_tokens,
                (COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) AS total_tokens,
                credits_used
            ');
    }

    /**
     * Resolve a company's display name (from the rekonek `companies` table).
     */
    public function companyName(string $companyId): ?string
    {
        return DB::connection(self::CONNECTION)
            ->table('companies')
            ->where('id', $companyId)
            ->value('name');
    }

    /**
     * Window cycle berjalan organisasi dari subscription_packages owner (default connection),
     * selaras dengan yang dipakai rekonek (subscription start..end). [null, null] bila tak ada.
     *
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    public function currentCycleWindow(string $companyId): array
    {
        $row = DB::table('subscription_packages')
            ->where('company_id', $companyId)
            ->orderByDesc('expired_at')
            ->first(['started_at', 'expired_at']);

        if (! $row) {
            return [null, null];
        }

        return [
            $row->started_at ? Carbon::parse($row->started_at) : null,
            $row->expired_at ? Carbon::parse($row->expired_at) : null,
        ];
    }

    /**
     * Total credit terpakai (net, termasuk adjustment) pada window cycle. Dipakai untuk
     * menghitung offset reset. Window null → jumlahkan seluruh baris company.
     */
    public function cycleCreditsUsed(string $companyId, ?CarbonInterface $start, ?CarbonInterface $end): int
    {
        // Reset menyasar pool CYCLE (saldo addon prepaid dibiarkan — sudah dibayar). Selaras dengan
        // rekonek yang kini menjumlah `cycle_credits_used` untuk gating cycle.
        return (int) DB::connection(self::CONNECTION)
            ->table(self::TABLE)
            ->where('company_id', $companyId)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            ->sum('cycle_credits_used');
    }

    /**
     * Tulis SATU baris penyesuaian credit ke rekonek (history aman; audit). `credits_used`
     * positif = konsumsi (kurangi sisa), negatif = menambah sisa. Idempotency tidak diperlukan
     * (tiap aksi admin = entri audit baru) — reference_id uuid unik memenuhi constraint.
     */
    public function recordAdjustment(
        string $companyId,
        int $signedCredits,
        string $type,
        ?string $note,
        ?string $actor
    ): void {
        DB::connection(self::CONNECTION)->table(self::TABLE)->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'feature' => $type,
            'reference_type' => $type,
            'reference_id' => (string) Str::uuid(),
            'request_id' => null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'credits_used' => $signedCredits,
            // Penyesuaian admin menyasar pool CYCLE agar terhitung oleh rekonek (usedThisCycle
            // menjumlah cycle_credits_used). Saldo addon prepaid tak disentuh.
            'cycle_credits_used' => $signedCredits,
            'addon_credits_used' => 0,
            'note' => $note,
            'actor' => $actor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

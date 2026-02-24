<?php

namespace Modules\SettingAffiliator\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Privilege\App\Models\Role;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;
use Modules\SettingAffiliator\App\Models\AffiliatorConfig;
use Modules\User\App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class SettingAffiliatorController extends Controller
{
    protected function getAffiliatorRole(): ?Role
    {
        return Role::whereRaw("trim(name) = ?", ['affiliator'])->first()
            ?? Role::where('name', 'affiliator')->first();
    }

    public function index()
    {
        return view('settingaffiliator::index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        $role = $this->getAffiliatorRole();
        if (! $role) {
            notify()->error('Role Affiliator tidak ditemukan. Silakan jalankan seeder role.');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $password = Str::random(32);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'is_active' => false,
            ]);
            $user->assignRole($role);
            DB::commit();
            notify()->success('Berhasil menambah data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            notify()->success('Berhasil mengubah data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        try {
            $user->delete();
            notify()->success('Berhasil menghapus data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back();
        }
    }

    public function detail($id)
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        $promoCodes = PromoCode::where('affiliator_user_id', $user->id)->orderBy('code')->get();
        $affiliatorConfig = AffiliatorConfig::where('user_id', $user->id)->first();
        $totalCommission = $this->computeTotalCommission($user->id, $affiliatorConfig);

        return view('settingaffiliator::detail', compact('user', 'promoCodes', 'affiliatorConfig', 'totalCommission'));
    }

    /**
     * API data untuk detail reporting: summary + list per tab (filter by promo).
     * Query: ?promo_id=all|uuid
     */
    public function detailData($id, Request $request): JsonResponse
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        $promoIds = PromoCode::where('affiliator_user_id', $user->id)->pluck('id');
        if ($request->filled('promo_id') && $request->promo_id !== 'all') {
            $promoIds = $promoIds->intersect(collect([$request->promo_id]));
        }
        if ($promoIds->isEmpty()) {
            return response()->json([
                'summary' => [
                    'register_count' => 0,
                    'new_purchase_total' => 0,
                    'new_purchase_count' => 0,
                    'renewal_total' => 0,
                    'renewal_count' => 0,
                ],
                'list_register' => [],
                'list_new_purchase' => [],
                'list_renewal' => [],
            ]);
        }

        $config = AffiliatorConfig::where('user_id', $user->id)->first();
        $pctB = (float) ($config->commission_value_registrasi ?? 0);
        $pctP = (float) ($config->commission_value_perpanjangan ?? 0);

        $baseUsage = fn () => PromoCodeUsage::whereIn('promo_code_id', $promoIds);

        $summary = [
            'register_count' => (clone $baseUsage())->where('status', 'R')->count(),
            'new_purchase_total' => 0,
            'new_purchase_count' => 0,
            'renewal_total' => 0,
            'renewal_count' => 0,
        ];

        $usagesB = (clone $baseUsage())->where('status', 'B')->get();
        $summary['new_purchase_count'] = $usagesB->count();
        $summary['new_purchase_total'] = round($usagesB->sum(fn ($u) => (float) ($u->discount_amount ?? 0) * ($pctB / 100)), 2);

        $usagesP = (clone $baseUsage())->where('status', 'P')->get();
        $summary['renewal_count'] = $usagesP->count();
        $summary['renewal_total'] = round($usagesP->sum(fn ($u) => (float) ($u->discount_amount ?? 0) * ($pctP / 100)), 2);

        $listRegister = (clone $baseUsage())
            ->where('status', 'R')
            ->with(['promoCode', 'registerContact'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($u, $i) {
                $contact = $u->registerContact;
                $meta = $u->metadata ?? [];
                return [
                    'no' => $i + 1,
                    'date' => $u->created_at?->format('d/m/Y H:i'),
                    'promo_code' => $u->promoCode?->code ?? '—',
                    'client_name' => $contact?->name ?? ($meta['name'] ?? ($meta['email'] ?? '—')),
                    'email' => $contact?->email ?? ($meta['email'] ?? '—'),
                ];
            })
            ->values()
            ->all();

        $listNewPurchase = (clone $baseUsage())
            ->where('status', 'B')
            ->with(['promoCode', 'invoice', 'customerContact'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($u, $i) use ($pctB) {
                $inv = $u->invoice;
                $clientName = $inv?->customer_name ?? $u->customerContact?->name ?? '—';
                $totalPurchase = $u->purchase_amount ?? $inv?->subtotal ?? 0;
                $discount = (float) ($u->discount_amount ?? 0);
                $commission = round($discount * ($pctB / 100), 2);
                return [
                    'no' => $i + 1,
                    'date' => $u->created_at?->format('d/m/Y H:i'),
                    'promo_code' => $u->promoCode?->code ?? '—',
                    'invoice_code' => $inv?->code ?? '—',
                    'client_name' => $clientName,
                    'total_purchase' => $totalPurchase,
                    'discount_amount' => $discount,
                    'commission' => $commission,
                ];
            })
            ->values()
            ->all();

        $listRenewal = (clone $baseUsage())
            ->where('status', 'P')
            ->with(['promoCode', 'invoice', 'customerContact'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($u, $i) use ($pctP) {
                $inv = $u->invoice;
                $clientName = $inv?->customer_name ?? $u->customerContact?->name ?? '—';
                $totalPurchase = $u->purchase_amount ?? $inv?->subtotal ?? 0;
                $discount = (float) ($u->discount_amount ?? 0);
                $commission = round($discount * ($pctP / 100), 2);
                return [
                    'no' => $i + 1,
                    'date' => $u->created_at?->format('d/m/Y H:i'),
                    'promo_code' => $u->promoCode?->code ?? '—',
                    'invoice_code' => $inv?->code ?? '—',
                    'client_name' => $clientName,
                    'total_purchase' => $totalPurchase,
                    'discount_amount' => $discount,
                    'commission' => $commission,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'summary' => $summary,
            'list_register' => $listRegister,
            'list_new_purchase' => $listNewPurchase,
            'list_renewal' => $listRenewal,
        ]);
    }

    /**
     * Total komisi dari usage status B (pembelian baru) dan P (perpanjangan).
     */
    protected function computeTotalCommission(string $affiliatorUserId, ?AffiliatorConfig $config): float
    {
        $promoIds = PromoCode::where('affiliator_user_id', $affiliatorUserId)->pluck('id');
        if ($promoIds->isEmpty() || ! $config) {
            return 0;
        }

        $usages = PromoCodeUsage::whereIn('promo_code_id', $promoIds)->whereIn('status', ['B', 'P'])->get();
        $total = 0;
        $pctRegistrasi = (float) ($config->commission_value_registrasi ?? 0);
        $pctPerpanjangan = (float) ($config->commission_value_perpanjangan ?? 0);

        foreach ($usages as $usage) {
            $discount = (float) ($usage->discount_amount ?? 0);
            $pct = $usage->status === 'B' ? $pctRegistrasi : $pctPerpanjangan;
            $total += $discount * ($pct / 100);
        }

        return round($total, 2);
    }

    public function getConfig($id): JsonResponse
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        $config = AffiliatorConfig::where('user_id', $user->id)->first();

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'commission_type_registrasi' => optional($config)->commission_type_registrasi ?? 'percentage',
            'commission_value_registrasi' => optional($config)->commission_value_registrasi,
            'commission_type_perpanjangan' => optional($config)->commission_type_perpanjangan ?? 'percentage',
            'commission_value_perpanjangan' => optional($config)->commission_value_perpanjangan,
        ]);
    }

    public function saveConfig(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'commission_type_registrasi' => 'required|in:percentage',
            'commission_value_registrasi' => 'required|numeric|min:0|max:100',
            'commission_type_perpanjangan' => 'required|in:percentage',
            'commission_value_perpanjangan' => 'required|numeric|min:0|max:100',
        ]);

        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        AffiliatorConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'commission_type_registrasi' => $request->commission_type_registrasi,
                'commission_value_registrasi' => $request->commission_value_registrasi,
                'commission_type_perpanjangan' => $request->commission_type_perpanjangan,
                'commission_value_perpanjangan' => $request->commission_value_perpanjangan,
            ]
        );

        notify()->success('Berhasil menyimpan konfigurasi komisi affiliator.');

        return redirect()->route('setting-affiliator.index');
    }

    public function getPromoCodes($id): JsonResponse
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        $promoCodes = PromoCode::where('affiliator_user_id', $user->id)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_active', 'usage_limit', 'used_count', 'start_date', 'end_date']);

        return response()->json([
            'success' => true,
            'data' => $promoCodes->map(function ($pc) {
                return [
                    'code' => $pc->code,
                    'name' => $pc->name,
                    'is_active' => $pc->is_active,
                    'usage_limit' => $pc->usage_limit,
                    'used_count' => $pc->used_count,
                    'start_date' => $pc->start_date?->format('d/m/Y'),
                    'end_date' => $pc->end_date?->format('d/m/Y'),
                ];
            }),
        ]);
    }

    public function datatable(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        });
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('checkbox', fn ($user) => view('settingaffiliator::table_partials._checkbox', ['user' => $user]))
            ->addColumn('name', fn ($user) => view('settingaffiliator::table_partials._name', ['user' => $user]))
            ->addColumn('email', fn ($user) => view('settingaffiliator::table_partials._email', ['user' => $user]))
            ->addColumn('promo_code', fn ($user) => view('settingaffiliator::table_partials._promo_code', ['user' => $user]))
            ->addColumn('action', fn ($user) => view('settingaffiliator::table_partials._action', ['user' => $user]))
            ->rawColumns(['checkbox', 'promo_code', 'action'])
            ->make(true);
    }
}

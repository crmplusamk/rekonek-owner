<?php

namespace Modules\Subscription\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * Operasi CRUD baca/status/hapus + datatable untuk admin Subscription (service pattern).
 * Catatan: pembuatan subscription (store) dan snapshot build-on-subscribe tetap di
 * SubscriptionRepository + SubscriptionService (build-on-subscribe fase 1) karena terkait
 * alur billing yang masih perlu penyesuaian bisnis.
 */
class SubscriptionCrudService
{
    public function detail(string $id): SubscriptionPackage
    {
        return SubscriptionPackage::with(['customer', 'package'])->findOrFail($id);
    }

    /** Aturan fitur ter-snapshot (grandfathering) milik subscription ini. */
    public function featureRules(string $id)
    {
        return DB::table('subscription_feature_rules as sfr')
            ->join('features as f', 'f.id', '=', 'sfr.feature_id')
            ->where('sfr.subscription_id', $id)
            ->orderBy('f.order')
            ->select('sfr.feature_id', 'f.name', 'f.key', 'sfr.limit', 'sfr.limit_type', 'sfr.included', 'sfr.visiblity', 'sfr.source')
            ->get();
    }

    public function datatable()
    {
        $query = SubscriptionPackage::with(['customer', 'package'])
            ->when(request()->search, function ($q) {
                $search = '%' . request()->search . '%';
                $q->where('code', 'ilike', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', $search));
            })
            ->when(request()->filled('status'), function ($q) {
                $q->where('is_active', request()->status === '1');
            })
            ->orderByDesc('created_at');

        return datatables()->of($query)
            ->addColumn('checkbox', fn ($subscription) => view('subscription::table_partials._checkbox', compact('subscription')))
            ->addColumn('customer', fn ($subscription) => view('subscription::table_partials._customer', compact('subscription')))
            ->addColumn('code', fn ($subscription) => view('subscription::table_partials._code', compact('subscription')))
            ->addColumn('package', fn ($subscription) => view('subscription::table_partials._package', compact('subscription')))
            ->addColumn('started_at', fn ($subscription) => view('subscription::table_partials._started-at', compact('subscription')))
            ->addColumn('expired_at', fn ($subscription) => view('subscription::table_partials._expired-at', compact('subscription')))
            ->addColumn('status', fn ($subscription) => view('subscription::table_partials._status', compact('subscription')))
            ->addColumn('action', fn ($subscription) => view('subscription::table_partials._action', compact('subscription')))
            ->make();
    }
}

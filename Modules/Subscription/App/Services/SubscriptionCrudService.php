<?php

namespace Modules\Subscription\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * Operasi CRUD baca/status/hapus + datatable untuk admin Subscription (service pattern).
 * Catatan: pembuatan subscription (store) dan snapshot build-on-subscribe tetap di
 * SubscriptionService (build-on-subscribe fase 1) karena terkait
 * alur billing yang masih perlu penyesuaian bisnis.
 */
class SubscriptionCrudService
{
    public function detail(string $id): SubscriptionPackage
    {
        return SubscriptionPackage::with(['customer', 'package'])->findOrFail($id);
    }

    /**
     * Addon AKTIF milik company pemilik subscription ini.
     *
     * Catatan penting: subscription_addons TIDAK punya FK ke subscription_packages — addon adalah
     * "entitlement berjalan" per (company, addon) yang di-upsert lintas cycle (lihat
     * SubscriptionService::updateAddon). Karena itu addon TIDAK bisa dipetakan ke satu subscription
     * tertentu; scope yang jujur & konsisten dgn kode existing (GenerateRenewalInvoiceJob, app
     * entitlement resolver) adalah company_id + is_active. UI menampilkannya sebagai "addon company",
     * bukan "addon cycle ini".
     *
     * Kolom harga: addons.price adalah harga per BLOK sebesar addons.charge unit (blok=1 utk WA/CS,
     * 1000 utk MAU/AI Credit). Jumlah blok = subscription_addons.charge / addons.charge.
     */
    public function addons(SubscriptionPackage $subscription)
    {
        return DB::table('subscription_addons as sa')
            ->join('addons as a', 'a.id', '=', 'sa.addon_id')
            ->leftJoin('features as f', 'f.id', '=', 'a.feature_id')
            ->where('sa.company_id', $subscription->company_id)
            ->where('sa.is_active', true)
            ->orderBy('a.name')
            ->select(
                'sa.id',
                'sa.code',
                'sa.addon_id',
                'a.name as addon_name',
                'a.description',
                'a.billing_type',
                'f.key as feature_key',
                'sa.charge as units',
                'a.charge as block_size',
                'a.price',
                'sa.started_at',
                'sa.expired_at',
                'sa.is_active'
            )
            ->get();
    }

    /** Master paket aktif untuk pilihan ubah paket (upgrade/downgrade). */
    public function packages()
    {
        return DB::table('packages')
            ->where('is_active', true)
            ->orderBy('order')
            ->select('id', 'name', 'price', 'order')
            ->get();
    }

    /** Master addon aktif (katalog) untuk pilihan tambah addon. */
    public function addonCatalog()
    {
        return DB::table('addons as a')
            ->leftJoin('features as f', 'f.id', '=', 'a.feature_id')
            ->where('a.is_active', true)
            ->orderBy('a.name')
            ->select('a.id', 'a.name', 'a.charge as block_size', 'a.price', 'f.key as feature_key')
            ->get();
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

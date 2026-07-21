<?php

namespace Modules\Subscription\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionAdminService;
use Modules\Subscription\App\Services\SubscriptionCrudService;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * Detail subscription admin owner: view + override MANUAL. Selain snapshot aturan fitur, admin juga
 * dapat memanipulasi langganan langsung (ubah paket upgrade/downgrade, kelola addon) via
 * SubscriptionAdminService — di luar alur checkout/billing (koreksi/override admin, tanpa invoice).
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionCrudService $service,
        private SubscriptionFeatureRuleService $ruleService,
        private SubscriptionAdminService $adminService
    ) {}

    public function index()
    {
        return view('subscription::index');
    }

    public function show($id)
    {
        try {
            $data = $this->service->detail($id);
            $rules = $this->service->featureRules($id);
            $addons = $this->service->addons($data);
            $packages = $this->service->packages();
            $addonCatalog = $this->service->addonCatalog();
            return view('subscription::show', compact('data', 'rules', 'addons', 'packages', 'addonCatalog'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function datatable(Request $request)
    {
        return $this->service->datatable();
    }

    /** Override manual satu baris aturan snapshot (source='manual'). */
    public function updateRule(Request $request, $id, $featureId)
    {
        $request->validate([
            'limit_mode' => ['required', 'in:none,unlimited,limited'],
            'limit' => ['required_if:limit_mode,limited', 'nullable', 'integer', 'min:1'],
            'limit_type' => ['required_if:limit_mode,limited', 'nullable', 'in:max,day,month,time'],
        ]);

        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $mode = $request->limit_mode;

            $this->ruleService->setManualRule($subscription, $featureId, [
                'included' => $request->input('included') === 'on',
                'visiblity' => $request->input('visiblity', 'on') === 'on',
                'limit' => $mode === 'limited' ? (string) (int) $request->limit : ($mode === 'unlimited' ? '-1' : null),
                'limit_type' => $mode === 'limited' ? $request->limit_type : null,
            ]);

            notify()->success('Aturan fitur berhasil di-override (manual)');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    /** Reset satu baris aturan ke nilai paket saat ini (source='package'). */
    public function resetRule($id, $featureId)
    {
        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $this->ruleService->resetRule($subscription, $featureId);
            notify()->success('Aturan fitur direset ke paket');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    /** Ubah paket langganan (upgrade/downgrade) in-place + rebuild snapshot fitur. */
    public function changePackage(Request $request, $id)
    {
        $request->validate([
            'package_id' => ['required', 'uuid', 'exists:packages,id'],
            'termin' => ['required', 'in:month,year'],
            'termin_duration' => ['required', 'integer', 'min:1', 'max:120'],
            'started_at' => ['required', 'date'],
            'is_trial' => ['required', 'in:trial,subs'],
        ]);

        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $this->adminService->changePackage($subscription, $request->only([
                'package_id', 'termin', 'termin_duration', 'started_at', 'is_trial',
            ]));
            notify()->success('Paket langganan berhasil diubah');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    /** Tambah addon untuk company pemilik subscription ini. */
    public function storeAddon(Request $request, $id)
    {
        $request->validate([
            'addon_id' => ['required', 'uuid', 'exists:addons,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'started_at' => ['required', 'date'],
            'expired_at' => ['required', 'date', 'after_or_equal:started_at'],
        ]);

        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $this->adminService->addAddon($subscription, $request->only([
                'addon_id', 'quantity', 'started_at', 'expired_at',
            ]));
            notify()->success('Addon berhasil ditambahkan');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    /** Ubah addon aktif (set nilai eksak). */
    public function updateAddon(Request $request, $id, $addonId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'started_at' => ['required', 'date'],
            'expired_at' => ['required', 'date', 'after_or_equal:started_at'],
        ]);

        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $addon = SubscriptionAddon::where('company_id', $subscription->company_id)->findOrFail($addonId);
            $this->adminService->updateAddon($addon, [
                'quantity' => $request->quantity,
                'started_at' => $request->started_at,
                'expired_at' => $request->expired_at,
                'is_active' => $request->input('is_active') === 'on',
            ]);
            notify()->success('Addon berhasil diperbarui');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    /** Hapus addon (hard delete). */
    public function destroyAddon($id, $addonId)
    {
        try {
            $subscription = SubscriptionPackage::findOrFail($id);
            $addon = SubscriptionAddon::where('company_id', $subscription->company_id)->findOrFail($addonId);
            $this->adminService->removeAddon($addon);
            notify()->success('Addon berhasil dihapus');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }
}

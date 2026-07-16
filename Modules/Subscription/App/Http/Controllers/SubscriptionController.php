<?php

namespace Modules\Subscription\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionCrudService;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * Subscription bersifat VIEW-ONLY di admin owner (list + detail). Pembuatan/perubahan langganan
 * terjadi lewat alur checkout (Modules/Checkout), bukan CRUD admin. Pengecualian: override MANUAL
 * baris snapshot aturan fitur (deal khusus per-company) — bukan mengubah record subscription.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionCrudService $service,
        private SubscriptionFeatureRuleService $ruleService
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
            return view('subscription::show', compact('data', 'rules'));
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
}

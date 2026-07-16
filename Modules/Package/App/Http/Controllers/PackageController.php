<?php

namespace Modules\Package\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Package\App\Http\Requests\StorePackageRequest;
use Modules\Package\App\Http\Requests\UpdatePackageRequest;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Services\PackageCrudService;
use Modules\Subscription\App\Jobs\PushPackageRulesJob;
use Modules\Subscription\App\Jobs\ReconcilePackageFeatureRulesJob;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

class PackageController extends Controller
{
    public function __construct(
        private PackageCrudService $service,
        private SubscriptionFeatureRuleService $ruleService
    ) {}

    public function index()
    {
        return view('package::index');
    }

    public function list(Request $request)
    {
        return Package::when(
            $request->search,
            fn ($query) => $query->where('name', 'ilike', '%' . $request->search . '%')
        )->paginate(10);
    }

    public function create()
    {
        try {
            return view('package::create', $this->service->formData());
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function store(StorePackageRequest $request)
    {
        try {
            $this->service->create($request->all());
            notify()->success('Berhasil membuat data package');
            return to_route('package.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        try {
            $data = $this->service->detail($id);
            return view('package::show', compact('data'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function edit($id)
    {
        try {
            $package = Package::findOrFail($id);
            $form = $this->service->formData($package);

            return view('package::edit', [
                'package' => $package,
                'features' => $form['features'],
                'tmpRules' => $form['rules'],
            ]);
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function update(UpdatePackageRequest $request, $id)
    {
        try {
            $this->service->update($request->all(), $id);

            // Rekonsiliasi keanggotaan fitur (tambah/hapus) ke snapshot subscriber aktif paket ini.
            // Nilai limit fitur existing tetap beku (grandfathering) — perubahan nilai lewat push terkontrol.
            ReconcilePackageFeatureRulesJob::dispatch($id);

            notify()->success('Berhasil mengubah data package');
            return to_route('package.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            if ($this->service->delete($id)) {
                notify()->success('Berhasil menghapus data package');
                return to_route('package.index');
            }

            notify()->warning('Package tidak dapat dihapus karena masih memiliki pelanggan');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {
            $this->service->toggleStatus($id);
            notify()->success('Berhasil mengubah status package');
            return to_route('package.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->service->datatable();
    }

    /** Daftar subscriber aktif paket (untuk picker & hitung dampak push terkontrol). */
    public function subscribers($id)
    {
        $subs = $this->ruleService->activeSubscriptions($id);

        return response()->json([
            'count' => $subs->count(),
            'data' => $subs->map(fn ($s) => [
                'id' => $s->id,
                'text' => ($s->customer->name ?? '-') . ' — ' . $s->code,
            ])->values(),
        ]);
    }

    /** Push terkontrol: terapkan aturan paket saat ini ke snapshot pelanggan terpilih. */
    public function pushRules(Request $request, $id)
    {
        $request->validate([
            'scope' => ['required', 'in:all,selected'],
            'subscription_ids' => ['required_if:scope,selected', 'array'],
            'subscription_ids.*' => ['string'],
        ], [], ['subscription_ids' => 'pelanggan']);

        try {
            $ids = $request->scope === 'selected' ? $request->subscription_ids : null;
            PushPackageRulesJob::dispatch($id, $ids, $request->boolean('overwrite_manual'));

            notify()->success('Penerapan aturan ke pelanggan sedang diproses di latar belakang.');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }
}

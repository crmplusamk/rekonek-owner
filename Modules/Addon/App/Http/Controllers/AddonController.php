<?php

namespace Modules\Addon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Addon\App\Http\Requests\StoreAddonRequest;
use Modules\Addon\App\Http\Requests\UpdateAddonRequest;
use Modules\Addon\App\Services\AddonCrudService;

class AddonController extends Controller
{
    public function __construct(private AddonCrudService $service) {}

    public function index()
    {
        $availableFeature = $this->service->availableFeatures();
        return view('addon::index', compact('availableFeature'));
    }

    public function store(StoreAddonRequest $request)
    {
        try {
            $this->service->create($request->validated());
            notify()->success('Berhasil membuat data addon');
            return to_route('addon.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function update(UpdateAddonRequest $request, $id)
    {
        try {
            $this->service->update($request->validated(), $id);
            notify()->success('Berhasil mengubah data addon');
            return to_route('addon.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function status($id)
    {
        try {
            $this->service->toggleStatus($id);
            notify()->success('Berhasil mengubah status addon');
            return to_route('addon.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            notify()->success('Berhasil menghapus data addon');
            return to_route('addon.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function datatable(Request $request)
    {
        return $this->service->datatable();
    }
}

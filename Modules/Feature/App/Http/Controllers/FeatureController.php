<?php

namespace Modules\Feature\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Feature\App\Http\Requests\StoreFeatureRequest;
use Modules\Feature\App\Http\Requests\UpdateFeatureRequest;
use Modules\Feature\App\Services\FeatureCrudService;

class FeatureController extends Controller
{
    public function __construct(private FeatureCrudService $service) {}

    public function index()
    {
        return view('feature::feature.index');
    }

    public function store(StoreFeatureRequest $request)
    {
        try {
            $this->service->create($request->all());
            notify()->success('Berhasil membuat data fitur');
            return to_route('feature.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function update(UpdateFeatureRequest $request, $id)
    {
        try {
            $this->service->update($request->all(), $id);
            notify()->success('Berhasil mengubah data fitur');
            return to_route('feature.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            notify()->success('Berhasil menghapus data fitur');
            return to_route('feature.index');
        } catch (\Exception $e) {
            notify()->error('Fitur tidak dapat dihapus. ' . $e->getMessage());
            return back();
        }
    }

    public function datatable(Request $request)
    {
        return $this->service->datatable();
    }
}

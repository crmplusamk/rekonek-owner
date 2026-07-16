<?php

namespace Modules\Feature\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Feature\App\Http\Requests\StoreFeatureCategoryRequest;
use Modules\Feature\App\Http\Requests\UpdateFeatureCategoryRequest;
use Modules\Feature\App\Services\FeatureCategoryCrudService;

class FeatureCategoryController extends Controller
{
    public function __construct(private FeatureCategoryCrudService $service) {}

    public function index()
    {
        return view('feature::category.index');
    }

    public function list()
    {
        return $this->service->getList();
    }

    public function store(StoreFeatureCategoryRequest $request)
    {
        try {
            $this->service->create($request->all());
            notify()->success('Berhasil membuat data kategori');
            return to_route('feature.category.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function update(UpdateFeatureCategoryRequest $request, $id)
    {
        try {
            $this->service->update($request->all(), $id);
            notify()->success('Berhasil mengubah data kategori');
            return to_route('feature.category.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            if ($this->service->delete($id)) {
                notify()->success('Berhasil menghapus data kategori');
                return to_route('feature.category.index');
            }

            notify()->warning('Kategori tidak dapat dihapus karena masih memiliki fitur');
            return back();
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

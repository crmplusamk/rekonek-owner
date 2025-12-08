<?php

namespace Modules\Feature\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Feature\App\Repositories\FeatureCategoryRepository;

class FeatureCategoryController extends Controller
{
    public $featureCategoryRepo;

    public function __construct(FeatureCategoryRepository $featureCategoryRepo)
    {
        $this->featureCategoryRepo = $featureCategoryRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('feature::category.index');
    }

    public function list()
    {
        return $this->featureCategoryRepo->getList();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $this->featureCategoryRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data kategori");
            return to_route('feature.category.index');

        } catch (\Exception $e) {

            DB::rollBack();
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {

            $data =  $this->featureCategoryRepo->detail($id);
            return view('feature::category.show', compact('data'));

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {

            $data =  $this->featureCategoryRepo->detail($id);
            return view('feature::category.edit', compact('data'));

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {

            $this->featureCategoryRepo->update($request->all(), $id);

            notify()->success("Berhasil mengubah data kategori");
            return to_route('feature.category.index');

        } catch (\Exception $e) {

            DB::rollBack();
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $data = $this->featureCategoryRepo->delete($id);

            if ($data != 403) {
                notify()->success("Berhasil menghapus data kategori");
                return to_route('feature.category.index');
            }

            notify()->warning("Kategori tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function datatable(Request $request)
    {
        return $this->featureCategoryRepo->datatable();
    }
}

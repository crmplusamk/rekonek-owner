<?php

namespace Modules\Feature\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Feature\App\Repositories\FeatureRepository;

class FeatureController extends Controller
{
    public $featureRepo;

    public function __construct(FeatureRepository $featureRepo)
    {
        $this->featureRepo = $featureRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('feature::feature.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {

            return view('feature::feature.create');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $this->featureRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data fitur");
            return to_route('feature.index');

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

            $data =  $this->featureRepo->detail($id);
            return view('feature::feature.show', compact('data'));

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

            $data =  $this->featureRepo->detail($id);
            return view('feature::feature.edit', compact('data'));

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
        DB::beginTransaction();
        try {

            $this->featureRepo->update($request->all(), $id);

            DB::commit();
            notify()->success("Berhasil mengubah data fitur");
            return to_route('feature.index');

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

            $data = $this->featureRepo->delete($id);

            if ($data != 403) {
                notify()->success("Berhasil menghapus data fitur");
                return to_route('feature.index');
            }

            notify()->warning("Fitur tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }


    public function datatable(Request $request)
    {
        return $this->featureRepo->datatable();
    }
}

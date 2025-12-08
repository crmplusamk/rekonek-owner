<?php

namespace Modules\Addon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Addon\App\Repositories\AddonRepository;
use Modules\Feature\App\Models\Feature;

class AddonController extends Controller
{
    public $addonRepo;

    public function __construct(AddonRepository $addonRepo)
    {
        $this->addonRepo = $addonRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $availableFeature = $this->addonRepo->getAvailableFeature();
        return view('addon::index', compact('availableFeature'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // try {

        //     return view('addon::create');

        // } catch (\Exception $e) {

        //     notify()->error("Terjadi kesalahan. ".$e->getMessage());
        //     return back();
        // }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $this->addonRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data addon");
            return to_route('addon.index');

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
        // try {

        //     $data =  $this->addonRepo->detail($id);
        //     return view('addon::show', compact('data'));

        // } catch (\Exception $e) {

        //     notify()->error("Terjadi kesalahan. ".$e->getMessage());
        //     return back();
        // }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // try {

        //     $data =  $this->addonRepo->detail($id);
        //     return view('addon::edit', compact('data'));

        // } catch (\Exception $e) {

        //     notify()->error("Terjadi kesalahan. ".$e->getMessage());
        //     return back();
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $this->addonRepo->update($request->all(), $id);

            DB::commit();
            notify()->success("Berhasil mengubah data addon");
            return to_route('addon.index');

        } catch (\Exception $e) {

            DB::rollBack();
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function status($id)
    {
        try {

            $this->addonRepo->status($id);

            notify()->success("Berhasil mengubah data addon");
            return to_route('addon.index');

        } catch (\Exception $e) {

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

            $data = $this->addonRepo->delete($id);

            if ($data != 403) {
                notify()->success("Berhasil menghapus data addon");
                return to_route('addon.index');
            }

            notify()->warning("Addon tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }


    public function datatable(Request $request)
    {
        return $this->addonRepo->datatable();
    }
}

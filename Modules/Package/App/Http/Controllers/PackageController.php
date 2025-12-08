<?php

namespace Modules\Package\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Feature\App\Models\Feature;
use Modules\Feature\App\Repositories\FeatureRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Repositories\PackageRepository;

class PackageController extends Controller
{
    public $packageRepo;
    public $featureRepo;

    public function __construct(PackageRepository $packageRepo, FeatureRepository $featureRepo)
    {
        $this->packageRepo = $packageRepo;
        $this->featureRepo = $featureRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('package::index');
    }

    public function list(Request $request)
    {
        $packages = Package::when($request->search, function($query) {
            $query->where('is_active', true)->paginate(10);
        })
        ->paginate(10);

        return $packages;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {

            $features = $this->featureRepo->list();
            return view('package::create', compact('features'));

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

            $this->packageRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data package");
            return to_route('package.index');

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

            $data =  $this->packageRepo->detail($id);
            return view('package::show', compact('data'));

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

            $package = $this->packageRepo->getById($id);
            $features = $this->featureRepo->list();
            $rules = DB::table('package_feature')->where('package_id', $package->id)->get();

            $tmpRules = [];
            foreach($rules as $rule) {
                $tmpRules[$rule->feature_id] = $rule;
            }

            return view('package::edit', compact('package', 'features', 'tmpRules'));

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

            $this->packageRepo->update($request->all(), $id);

            notify()->success("Berhasil mengubah data package");
            return to_route('package.index');

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

            $user = $this->packageRepo->delete($id);

            if ($user != 403) {
                notify()->success("Berhasil menghapus data package");
                return to_route('package.index');
            }

            notify()->warning("Package tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {

            $this->packageRepo->status($id);

            notify()->success("Berhasil mengubah data package");
            return to_route('package.index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->packageRepo->datatable();
    }
}

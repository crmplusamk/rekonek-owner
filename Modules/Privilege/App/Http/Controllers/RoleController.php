<?php

namespace Modules\Privilege\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Privilege\App\Services\RoleService;

class RoleController extends Controller
{

    public $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('privilege::role.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('privilege::role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $this->roleService->create($request->all());

            notify()->success("Berhasil membuat data role");
            return to_route('role.index');

        } catch (\Exception $e) {

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

            $role =  $this->roleService->detail($id);
            return view('privilege::role.show', compact('role'));

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

            $role =  $this->roleService->getById($id);
            return view('privilege::role.edit', compact('role'));

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

            $this->roleService->update($request->all(), $id);

            notify()->success("Berhasil mengubah data role");
            return to_route('role.index');

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

            $role = $this->roleService->delete($id);
            if ($role != 403) {
                notify()->success("Berhasil ". ($role == 200 ? 'menonaktifkan' : 'menghapus') ." data role");
                return to_route('role.index');
            }

            notify()->warning("Role default tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {

            $this->roleService->status($id);

            notify()->success("Berhasil mengubah data role");
            return to_route('role.index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->roleService->datatable();
    }
}

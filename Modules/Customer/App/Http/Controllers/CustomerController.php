<?php

namespace Modules\Customer\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Customer\App\Http\Requests\CustomerCreateRequest;
use Modules\Customer\App\Http\Requests\CustomerUpdateRequest;
use Modules\Customer\App\Repositories\CustomerRepository;

class CustomerController extends Controller
{
    public $customerRepo;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepo = $customerRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('customer::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {

            return view('customer::create');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function list(Request $request)
    {
        return $this->customerRepo->list($request->all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerCreateRequest $request)
    {
        DB::beginTransaction();
        try {

            $this->customerRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data customer");
            return to_route('customer.index');

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

            // $data =  $this->customerRepo->detail($id);
            // return view('customer::show', compact('data'));

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

            $data =  $this->customerRepo->detail($id);
            return view('customer::edit', compact('data'));

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request, $id)
    {
        try {

            $this->customerRepo->update($request->all(), $id);

            notify()->success("Berhasil mengubah data customer");
            return to_route('customer.index');

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

            $user = $this->customerRepo->delete($id);

            if ($user != 403) {
                notify()->success("Berhasil menghapus data customer");
                return to_route('customer.index');
            }

            notify()->warning("Customer tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {

            $this->customerRepo->status($id);

            notify()->success("Berhasil mengubah data customer");
            return to_route('customer.index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->customerRepo->datatable();
    }
}

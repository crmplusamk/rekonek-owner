<?php

namespace Modules\Subscription\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Client\App\Services\CompanyService;
use Modules\Subscription\App\Repositories\SubscriptionRepository;

class SubscriptionController extends Controller
{
    public $subscriptionRepo;

    public function __construct(SubscriptionRepository $subscriptionRepo)
    {
        $this->subscriptionRepo = $subscriptionRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('subscription::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {

            return view('subscription::create');

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

            $data = $this->subscriptionRepo->create($request->all());

            // $api = new CompanyService;
            // $api = $api->generate($data->customer);

            // if (isset($api['error'])) {
            //     DB::rollBack();
            //     notify()->error("Terjadi kesalahan generate company. ".$api['message']);
            //     return back()->withInput();
            // }

            // DB::commit();
            // notify()->success("Berhasil membuat data subscription");
            // return to_route('subscription.index');

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

            $data =  $this->subscriptionRepo->detail($id);
            return view('subscription::show', compact('data'));

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

            $data =  $this->subscriptionRepo->detail($id);
            return view('subscription::edit', compact('data'));

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

            $this->subscriptionRepo->update($request->all(), $id);

            notify()->success("Berhasil mengubah data subscription");
            return to_route('subscription.index');

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

            $user = $this->subscriptionRepo->delete($id);

            if ($user != 403) {
                notify()->success("Berhasil menghapus data subscription");
                return to_route('subscription.index');
            }

            notify()->warning("Subscription tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {

            $this->subscriptionRepo->status($id);

            notify()->success("Berhasil mengubah data subscription");
            return to_route('subscription.index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->subscriptionRepo->datatable();
    }
}

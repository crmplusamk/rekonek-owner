<?php

namespace Modules\Subscription\App\Repositories;

use Illuminate\Support\Str;
use Modules\Package\App\Models\Package;
use Modules\Subscription\App\Models\SubscriptionPackage;

class SubscriptionRepository
{
    public function create($request)
    {
        $data = SubscriptionPackage::create([
            'code' => Str::upper(Str::random(5)),
            'customer_id' => $request['customer_id'],
            'package_id' => $request['package_id'],
            'started_at' => $request['started_at'],
            'expired_at' => $request['expired_at'],
            'is_active' => $request['is_active'] ?? false,
            'company_id' => $request['company_id'] ?? null
        ]);

        return $data;
    }

    public function getById($id)
    {
        $data = SubscriptionPackage::findOrFail($id);
        return $data;
    }

    public function detail($id)
    {
        $data = SubscriptionPackage::where('id', $id)->with(['customer', 'package'])->first();
        return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);

        $data->update([
            'package_id' => $request['package'],
        ]);

        return $data;
    }

    public function delete($id)
    {
        $data = $this->getById($id);
        $data->delete();
        return 204;
    }

    public function status($id)
    {
        $data = $this->getById($id);
        $data->update([
            'is_active' => !$data->is_active
        ]);

        return $data;
    }

    public function activate($request)
    {
        $data = SubscriptionPackage::where([
            'customer_id' => $request['customer_id'],
            'is_active' => false,
            'started_at' => null,
        ])
        ->orderBy('created_at', 'desc')
        ->first();

        $package = Package::find($data->package_id);
        $expired = $package->duration_type == 'month' ? now()->addMonths($package->duration) : now()->addDays($package->duration);

        $data->update([
            'is_active' => true,
            'started_at' => now(),
            'expired_at' => $expired
        ]);

        return $data;
    }

    public function datatable()
    {
        $datatables = SubscriptionPackage::with(['customer', 'package']);

        return datatables()->of($datatables)

            ->addColumn('checkbox', function ($subscription) {
                return view('subscription::table_partials._checkbox', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('code', function ($subscription) {
                return view('subscription::table_partials._code', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('customer', function ($subscription) {
                return view('subscription::table_partials._customer', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('package', function ($subscription) {
                return view('subscription::table_partials._package', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('started_at', function ($subscription) {
                return view('subscription::table_partials._started-at', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('expired_at', function ($subscription) {
                return view('subscription::table_partials._expired-at', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('status', function ($subscription) {
                return view('subscription::table_partials._status', [
                    'subscription' => $subscription
                ]);
            })
            ->addColumn('action', function ($subscription) {
                return view('subscription::table_partials._action', [
                    'subscription' => $subscription,
                ]);
            })
            ->make();
    }
}

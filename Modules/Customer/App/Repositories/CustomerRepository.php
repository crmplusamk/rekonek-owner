<?php

namespace Modules\Customer\App\Repositories;

use Illuminate\Support\Str;
use Modules\Customer\App\Models\Customer;

class CustomerRepository
{

    public function list($request)
    {
        $data = Customer::when($request['search'] ?? null, function ($query) use ($request) {
            $query->where(function($query) use($request) {
                $query->where('name', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('code', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('phone', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('email', 'ilike', '%'.$request['search'].'%');
            });
        })
        ->where('is_active', true)
        ->paginate(10);

        return $data;
    }

    public function create($request)
    {
        $data = Customer::create([
            'name' => $request['name'],
            'code' => Str::upper(Str::random(8)),
            'email' => $request['email'],
            'phone' => $request['phone'],
            'company_id' => Str::uuid(),
            'is_active' => true,
            'is_customer' => true,
        ]);

        return $data;
    }

    public function getById($id)
    {
        $data = Customer::findOrFail($id);
        return $data;
    }

    public function getByCompanyId($id)
    {
        $data = Customer::where("company_id", $id)->first();
        return $data;
    }

    public function detail($id)
    {
        $data = Customer::findOrFail($id);
        return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
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

    public function datatable()
    {
        $datatables = Customer::when(request()->search, function ($query) {
                $query->where(function($query) {
                    $query->where('name', 'ilike', '%'.request()->search.'%')
                        ->orWhere('code', 'ilike', '%'.request()->search.'%')
                        ->orWhere('phone', 'ilike', '%'.request()->search.'%')
                        ->orWhere('email', 'ilike', '%'.request()->search.'%');
                });
            })
            ->when(request()->order[0], function ($query) {
                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                $orderMappings = [
                    "1" => 'name',
                    "2" => 'code',
                    "4" => 'created_at',
                ];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            })
        ->orderBy('created_at', 'desc');

        return datatables()->of($datatables)

            ->addColumn('checkbox', function ($customer) {
                return view('customer::table_partials._checkbox', [
                    'customer' => $customer
                ]);
            })
            ->editColumn('created_at', function ($customer) {
                return $customer->created_at->format('d M Y H:i');
            })
            ->addColumn('phone', function ($customer) {
                return view('customer::table_partials._phone', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('code', function ($customer) {
                return view('customer::table_partials._code', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('email', function ($customer) {
                return view('customer::table_partials._email', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('status', function ($customer) {
                return view('customer::table_partials._status', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('action', function ($customer) {
                return view('customer::table_partials._action', [
                    'customer' => $customer,
                ]);
            })
            ->make();
    }
}

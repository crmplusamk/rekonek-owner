<?php

namespace Modules\Addon\App\Repositories;

use Illuminate\Support\Str;
use Modules\Addon\App\Models\Addon;
use Modules\Feature\App\Models\Feature;

class AddonRepository
{

    public function list()
    {
        // $features = addon::s_parent', true)->with('childs')->get();
        // return $features;
    }

    public function getAvailableFeature()
    {
        return Feature::where('is_addon', true)->get();
    }

    public function create($request)
    {
        $data = Addon::create([
            'code' => Str::upper(Str::random(5)),
            'customer_id' => $request['customer_id'],
            'addon_id' => $request['addon_id'],
            'charge' => $request['charge'],
            'started_at' => $request['started_at'],
            'expired_at' => $request['expired_at'],
            'is_active' => $request['is_active'] ?? false
        ]);

        return $data;
    }

    public function getById($id)
    {
        $data = Addon::find($id);
        return $data;
    }

    public function detail($id)
    {
        // $data = addon::where('id', $id)->with('roles')->first();
        // return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'charge' => $request['charge'],
            'price' => $request['price'],
            'description' => $request['description'] ?? null,
            'is_active' => true,
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
        $datatables = Addon::when(request()->search, function ($query) {
                $query->where('name', 'ilike', '%'.request()->search.'%');
            })
            ->when(request()->order[0], function ($query) {
                $orderMappings = [
                    "1" => 'name',
                ];

                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            });

        return datatables()->of($datatables)

            ->addColumn('checkbox', function ($addon) {
                return view('addon::table_partials._checkbox', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('name', function ($addon) {
                return view('addon::table_partials._name', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('feature', function ($addon) {
                return view('addon::table_partials._feature', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('charge', function ($addon) {
                return view('addon::table_partials._charge', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('price', function ($addon) {
                return view('addon::table_partials._price', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('is_active', function ($addon) {
                return view('addon::table_partials._is-active', [
                    'addon' => $addon
                ]);
            })
            ->addColumn('action', function ($addon) {
                return view('addon::table_partials._action', [
                    'addon' => $addon
                ]);
            })
            ->make();
    }
}

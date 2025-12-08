<?php

namespace Modules\Feature\App\Repositories;

use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;

class FeatureRepository
{

    public function list()
    {
        $features = Feature::where('is_parent', false)->get();
        return $features;
    }

    public function create($request)
    {
        $data = Feature::create([
            'name' => $request['name'],
            'key' => str_replace('-', '_', Str::slug($request['name'])),
            'parent_id' => $request['parent'],
            'is_parent' => false,
            'is_addon' => $request['is_addon'] == "on" ? true : false
        ]);

        return $data;
    }

    public function getById($id)
    {
        $data = Feature::findOrFail($id);
        return $data;
    }

    public function detail($id)
    {
        // $data = feature::feature.where('id', $id)->with('roles')->first();
        // return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'key' => str_replace('-', '_', Str::slug($request['name'])),
            'parent_id' => $request['parent'],
            'is_addon' => $request['is_addon'] == "on" ? true : false
        ]);

        return $data;
    }

    public function delete($id)
    {
        // $data = $this->getById($id);
        // if (!$data->is_delete) return 403;

        // $data->delete();
        // return 204;
    }

    public function datatable()
    {
        $datatables = Feature::where('is_parent', false)->with('parent')
            ->when(request()->search, function ($query) {
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

            ->addColumn('checkbox', function ($feature) {
                return view('feature::feature.table_partials._checkbox', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('name', function ($feature) {
                return view('feature::feature.table_partials._name', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('key', function ($feature) {
                return view('feature::feature.table_partials._key', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('category', function ($feature) {
                return view('feature::feature.table_partials._category', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('addon', function ($feature) {
                return view('feature::feature.table_partials._addon', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('created_at', function ($feature) {
                return view('feature::feature.table_partials._created-at', [
                    'feature' => $feature
                ]);
            })
            ->addColumn('action', function ($feature) {
                return view('feature::feature.table_partials._action', [
                    'feature' => $feature,
                ]);
            })
            ->make();
    }
}

<?php

namespace Modules\Feature\App\Repositories;

use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;

class FeatureCategoryRepository
{

    public function getList()
    {
        $data = Feature::where('is_parent', true)->when(request()->search, function ($query) {
                $query->where('name', 'ilike', '%'.request()->search.'%');
        })->paginate(10);

        return $data;
    }

    public function create($request)
    {
        $data = Feature::create([
            'name' => $request['name'],
            'key' => str_replace('-', '_', Str::slug($request['name'])),
            'order' => Feature::orderBy('order', 'desc')->first()->order + 1,
            'is_parent' => true
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
        $data = $this->getById($id);
        return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'key' => str_replace('-', '_', Str::slug($request['name'])),
        ]);

        return $data;
    }

    public function delete($id)
    {
        $data = $this->getById($id);
        if ($data->childs()->exists()) return 403;

        $data->delete();
        return 204;
    }

    public function datatable()
    {
        $datatables = Feature::where('is_parent', true)->withCount('childs')
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

            ->addColumn('checkbox', function ($category) {
                return view('feature::category.table_partials._checkbox', [
                    'category' => $category
                ]);
            })
            ->addColumn('name', function ($category) {
                return view('feature::category.table_partials._name', [
                    'category' => $category
                ]);
            })
            ->addColumn('features_count', function ($category) {
                return view('feature::category.table_partials._features-count', [
                    'category' => $category
                ]);
            })
            ->addColumn('created_at', function ($category) {
                return view('feature::category.table_partials._created-at', [
                    'category' => $category
                ]);
            })
            ->addColumn('action', function ($category) {
                return view('feature::category.table_partials._action', [
                    'category' => $category,
                ]);
            })
            ->make();
    }
}

<?php

namespace Modules\Feature\App\Services;

use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;

/**
 * CRUD fitur (leaf, is_parent=false) — service pattern. Operasi tulis fitur.
 */
class FeatureCrudService
{
    public function create(array $data): Feature
    {
        return Feature::create([
            'name' => $data['name'],
            'key' => str_replace('-', '_', Str::slug($data['name'])),
            'parent_id' => $data['parent'],
            'is_parent' => false,
            'is_addon' => ($data['is_addon'] ?? null) === 'on',
        ]);
    }

    public function update(array $data, string $id): Feature
    {
        $feature = Feature::findOrFail($id);
        $feature->update([
            'name' => $data['name'],
            'key' => str_replace('-', '_', Str::slug($data['name'])),
            'parent_id' => $data['parent'],
            'is_addon' => ($data['is_addon'] ?? null) === 'on',
        ]);
        return $feature;
    }

    public function delete(string $id): bool
    {
        Feature::findOrFail($id)->delete();
        return true;
    }

    public function datatable()
    {
        $query = Feature::where('is_parent', false)->with('parent')
            ->when(request()->search, function ($q) {
                $q->where('name', 'ilike', '%' . request()->search . '%');
            })
            ->when(request()->input('order.0'), function ($q) {
                $map = ['1' => 'name'];
                $column = request()->input('order.0.column');
                $dir = request()->input('order.0.dir');
                if (isset($map[$column])) {
                    $q->orderBy($map[$column], $dir)->orderBy('id', 'desc');
                }
            });

        return datatables()->of($query)
            ->addColumn('checkbox', fn ($feature) => view('feature::feature.table_partials._checkbox', compact('feature')))
            ->addColumn('name', fn ($feature) => view('feature::feature.table_partials._name', compact('feature')))
            ->addColumn('key', fn ($feature) => view('feature::feature.table_partials._key', compact('feature')))
            ->addColumn('category', fn ($feature) => view('feature::feature.table_partials._category', compact('feature')))
            ->addColumn('addon', fn ($feature) => view('feature::feature.table_partials._addon', compact('feature')))
            ->addColumn('created_at', fn ($feature) => view('feature::feature.table_partials._created-at', compact('feature')))
            ->addColumn('action', fn ($feature) => view('feature::feature.table_partials._action', compact('feature')))
            ->make();
    }
}

<?php

namespace Modules\Feature\App\Services;

use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;

/**
 * CRUD kategori fitur (is_parent=true) — service pattern.
 */
class FeatureCategoryCrudService
{
    /** Dropdown/paginated list kategori (dipakai select2 di modal fitur). */
    public function getList()
    {
        return Feature::where('is_parent', true)
            ->when(request()->search, fn ($q) => $q->where('name', 'ilike', '%' . request()->search . '%'))
            ->paginate(10);
    }

    public function create(array $data): Feature
    {
        return Feature::create([
            'name' => $data['name'],
            'key' => str_replace('-', '_', Str::slug($data['name'])),
            'order' => (int) (Feature::max('order') ?? 0) + 1,
            'is_parent' => true,
        ]);
    }

    public function update(array $data, string $id): Feature
    {
        $category = Feature::findOrFail($id);
        $category->update([
            'name' => $data['name'],
            'key' => str_replace('-', '_', Str::slug($data['name'])),
        ]);
        return $category;
    }

    /** @return bool false bila kategori masih punya fitur (tak boleh dihapus). */
    public function delete(string $id): bool
    {
        $category = Feature::findOrFail($id);
        if ($category->childs()->exists()) {
            return false;
        }
        $category->delete();
        return true;
    }

    public function datatable()
    {
        $query = Feature::where('is_parent', true)->withCount('childs')
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
            ->addColumn('checkbox', fn ($category) => view('feature::category.table_partials._checkbox', compact('category')))
            ->addColumn('name', fn ($category) => view('feature::category.table_partials._name', compact('category')))
            ->addColumn('features_count', fn ($category) => view('feature::category.table_partials._features-count', compact('category')))
            ->addColumn('created_at', fn ($category) => view('feature::category.table_partials._created-at', compact('category')))
            ->addColumn('action', fn ($category) => view('feature::category.table_partials._action', compact('category')))
            ->make();
    }
}

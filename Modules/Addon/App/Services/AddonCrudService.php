<?php

namespace Modules\Addon\App\Services;

use Modules\Addon\App\Models\Addon;
use Modules\Feature\App\Models\Feature;

/**
 * CRUD katalog Addon (service pattern). Addon = definisi katalog (feature_id, name, charge, price,
 * description), BUKAN pembelian addon langganan (itu subscription_addons).
 */
class AddonCrudService
{
    /** Fitur yang boleh dijadikan addon. */
    public function availableFeatures()
    {
        return Feature::where('is_addon', true)->get();
    }

    public function create(array $data): Addon
    {
        return Addon::create([
            'feature_id' => $data['feature'],
            'name' => $data['name'],
            'charge' => $data['charge'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'is_active' => true,
        ]);
    }

    public function update(array $data, string $id): Addon
    {
        $addon = Addon::findOrFail($id);
        $addon->update([
            'name' => $data['name'],
            'charge' => $data['charge'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
        ]);
        return $addon;
    }

    public function toggleStatus(string $id): Addon
    {
        $addon = Addon::findOrFail($id);
        $addon->update(['is_active' => ! $addon->is_active]);
        return $addon;
    }

    public function delete(string $id): bool
    {
        Addon::findOrFail($id)->delete();
        return true;
    }

    public function datatable()
    {
        $query = Addon::with('feature')
            ->when(request()->search, function ($q) {
                $q->where('name', 'ilike', '%' . request()->search . '%');
            })
            ->when(request()->filled('status'), function ($q) {
                $q->where('is_active', request()->status === '1');
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
            ->addColumn('checkbox', fn ($addon) => view('addon::table_partials._checkbox', compact('addon')))
            ->addColumn('name', fn ($addon) => view('addon::table_partials._name', compact('addon')))
            ->addColumn('feature', fn ($addon) => view('addon::table_partials._feature', compact('addon')))
            ->addColumn('charge', fn ($addon) => view('addon::table_partials._charge', compact('addon')))
            ->addColumn('price', fn ($addon) => view('addon::table_partials._price', compact('addon')))
            ->addColumn('is_active', fn ($addon) => view('addon::table_partials._is-active', compact('addon')))
            ->addColumn('action', fn ($addon) => view('addon::table_partials._action', compact('addon')))
            ->make();
    }
}

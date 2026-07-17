<?php

namespace Modules\Package\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Feature\App\Models\Feature;
use Modules\Package\App\Models\Package;

/**
 * CRUD paket + aturan `package_feature` (service pattern). Membangun baris pivot dari kontrak
 * field form: visiblity[]/include[]/
 * limit_option[]/limit[]/limit_type[] dikunci per feature_id.
 */
class PackageCrudService
{
    /** Ambil paket master by id (dipakai lintas module oleh Checkout). */
    public function getById($id): Package
    {
        return Package::findOrFail($id);
    }

    public function create(array $data): Package
    {
        return DB::transaction(function () use ($data) {
            $package = Package::create($this->packageAttributes($data));
            $rows = $this->buildFeatureRows($package->id, $data);
            if ($rows) {
                DB::table('package_feature')->insert($rows);
            }
            return $package;
        });
    }

    public function update(array $data, string $id): Package
    {
        return DB::transaction(function () use ($data, $id) {
            $package = Package::findOrFail($id);
            $package->update($this->packageAttributes($data));

            DB::table('package_feature')->where('package_id', $package->id)->delete();
            $rows = $this->buildFeatureRows($package->id, $data);
            if ($rows) {
                DB::table('package_feature')->insert($rows);
            }
            return $package;
        });
    }

    public function toggleStatus(string $id): Package
    {
        $package = Package::findOrFail($id);
        $package->update(['is_active' => ! $package->is_active]);
        return $package;
    }

    /** @return bool false bila paket masih punya subscriber (tak boleh dihapus). */
    public function delete(string $id): bool
    {
        $package = Package::findOrFail($id);
        if ($package->subscription) {
            return false;
        }
        $package->delete();
        return true;
    }

    /** Data untuk form create/edit: daftar fitur + snapshot aturan existing (edit). */
    public function formData(?Package $package = null): array
    {
        $rules = [];
        if ($package) {
            foreach (DB::table('package_feature')->where('package_id', $package->id)->get() as $r) {
                $rules[$r->feature_id] = $r;
            }
        }

        return [
            'features' => Feature::where('is_parent', true)
                ->with(['childs' => fn ($q) => $q->orderBy('order')])
                ->orderBy('order')
                ->get(),
            'rules' => $rules,
        ];
    }

    /** Data untuk halaman detail (show): package + fitur + aturan (keyed feature id, with pivot). */
    public function detail(string $id): array
    {
        $package = Package::findOrFail($id);

        $rules = [];
        foreach ($package->features as $feature) {
            $rules[$feature->id] = $feature->toArray();
        }

        return [
            'package' => $package,
            'features' => Feature::where('is_parent', true)
                ->with(['childs' => fn ($q) => $q->orderBy('order')])
                ->orderBy('order')
                ->get(),
            'rules' => $rules,
        ];
    }

    public function datatable()
    {
        $query = Package::when(request()->search, function ($q) {
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
            ->addColumn('checkbox', fn ($package) => view('package::table_partials._checkbox', compact('package')))
            ->addColumn('name', fn ($package) => view('package::table_partials._name', compact('package')))
            ->addColumn('price', fn ($package) => view('package::table_partials._price', compact('package')))
            ->addColumn('status', fn ($package) => view('package::table_partials._status', compact('package')))
            ->addColumn('created_at', fn ($package) => view('package::table_partials._created-at', compact('package')))
            ->addColumn('action', fn ($package) => view('package::table_partials._action', compact('package')))
            ->make();
    }

    private function packageAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration' => (int) $data['duration'],
            'duration_type' => $data['duration_type'],
            'price' => (int) $data['price'],
            'is_publish' => ($data['is_publish'] ?? null) === 'on',
        ];
    }

    private function buildFeatureRows(string $packageId, array $data): array
    {
        $rows = [];
        foreach (($data['visiblity'] ?? []) as $featureId => $visiblity) {
            $rows[] = [
                'package_id' => $packageId,
                'feature_id' => $featureId,
                'limit' => isset($data['limit'][$featureId])
                    ? (int) $data['limit'][$featureId]
                    : (isset($data['limit_option'][$featureId])
                        ? ($data['limit_option'][$featureId] === 'unlimited' ? -1 : null)
                        : null),
                'limit_type' => $data['limit_type'][$featureId] ?? null,
                'included' => ($data['include'][$featureId] ?? null) === 'on',
                'visiblity' => $visiblity === 'on',
                'created_at' => now(),
            ];
        }
        return $rows;
    }
}

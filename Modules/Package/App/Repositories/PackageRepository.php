<?php

namespace Modules\Package\App\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Feature\App\Repositories\FeatureRepository;
use Modules\Package\App\Models\Package;
use Modules\User\App\Models\User;

class PackageRepository
{

    public $featureRepo;

    public function __construct(FeatureRepository $featureRepo)
    {
        $this->featureRepo = $featureRepo;
    }

    public function list()
    {
        $packages = Package::get();
        return $packages;
    }

    public function rules($package)
    {

        $rules = $package->features;
        $tmp = [];
        foreach ($rules as $feature) {
            $tmp[$feature->id] = $feature->toArray();
        }

        return $tmp;
    }

    public function create($request)
    {

        $package = Package::create([
           "name" => $request['name'],
           "description" => $request['description'] ?? null,
           "duration" => intval($request['duration']),
           "duration_type" => $request['duration_type'],
           "price" => intval($request['price']),
           "is_publish" => $request['is_publish'] == 'on' ? true : false
        ]);

        $tmp = [];
        foreach($request['visiblity'] as $featureId => $visiblity)
        {
            $tmp[] = [
                "package_id" => $package->id,
                "feature_id" => $featureId,
                "limit" => isset($request['limit'][$featureId]) ? intval($request['limit'][$featureId]) : (isset($request['limit_option'][$featureId]) ? ($request['limit_option'][$featureId]== "unlimited" ? -1 : null) : null),
                "limit_type" => isset($request['limit_type'][$featureId]) ? $request['limit_type'][$featureId] : null,
                "included" => $request['include'][$featureId] == "on" ? true : false,
                "visiblity" => $visiblity == "on" ? true : false,
                "created_at" => now()
            ];
        }

        DB::table('package_feature')->insert($tmp);
        return $package;
    }

    public function getById($id)
    {
        $data = Package::findOrFail($id);
        return $data;
    }

    public function getByName($name)
    {
        $data = Package::where("name", $name)->first();
        return $data;
    }

    public function detail($id)
    {
        $package = $this->getById($id);
        $features = $this->featureRepo->list();
        $rules = $this->rules($package);

        return [
            "package" => $package,
            "features" => $features,
            "rules" => $rules
        ];
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            "name" => $request['name'],
            "description" => $request['description'] ?? null,
            "duration" => intval($request['duration']),
            "duration_type" => $request['duration_type'],
            "price" => intval($request['price']),
           "is_publish" => $request['is_publish'] == 'on' ? true : false
        ]);

        $tmp = [];
        foreach($request['visiblity'] as $featureId => $visiblity)
        {
            $tmp[] = [
                "package_id" => $data->id,
                "feature_id" => $featureId,
                "limit" => isset($request['limit'][$featureId]) ? intval($request['limit'][$featureId]) : (isset($request['limit_option'][$featureId]) ? ($request['limit_option'][$featureId]== "unlimited" ? -1 : null) : null),
                "limit_type" => isset($request['limit_type'][$featureId]) ? $request['limit_type'][$featureId] : null,
                "included" => $request['include'][$featureId] == "on" ? true : false,
                "visiblity" => $visiblity == "on" ? true : false,
                "created_at" => now()
            ];
        }

        DB::table('package_feature')->where('package_id', $data->id)->delete();
        DB::table('package_feature')->insert($tmp);

        return $data;
    }

    public function delete($id)
    {
        $data = $this->getById($id);
        if ($data->subscription) return 403;

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
        $datatables = Package::when(request()->search, function ($query) {
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

            ->addColumn('checkbox', function ($package) {
                return view('package::table_partials._checkbox', [
                    'package' => $package
                ]);
            })
            ->addColumn('name', function ($package) {
                return view('package::table_partials._name', [
                    'package' => $package
                ]);
            })
            ->addColumn('price', function ($package) {
                return view('package::table_partials._price', [
                    'package' => $package
                ]);
            })
            ->addColumn('status', function ($package) {
                return view('package::table_partials._status', [
                    'package' => $package
                ]);
            })
            ->addColumn('created_at', function ($package) {
                return view('package::table_partials._created-at', [
                    'package' => $package
                ]);
            })
            ->addColumn('action', function ($package) {
                return view('package::table_partials._action', [
                    'package' => $package,
                ]);
            })
            ->make();
    }
}

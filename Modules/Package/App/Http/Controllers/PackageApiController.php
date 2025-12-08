<?php

namespace Modules\Package\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureListResource;
use App\Http\Resources\PackageFeatureListResource;
use Illuminate\Http\RedirectResponse;
use Modules\Feature\App\Models\Feature;
use Modules\Feature\App\Repositories\FeatureRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Repositories\PackageRepository;

class PackageApiController extends Controller
{

    public function index()
    {
        try {

            $packages = Package::with('features')->where([
                    'is_publish' => true,
                    'is_active' => true
                ])
                ->orderBy("order", "asc")
                ->get();

            $features = Feature::whereNull('parent_id')
                ->with('childs')
                ->get();

            $packages = PackageFeatureListResource::collection($packages);
            $features = FeatureListResource::collection($features);

            return response()->json([
                "success" => true,
                "message" => "Ok",
                "data" => [
                    "packages" => $packages,
                    "features" => $features
                ]
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                "error" => true,
                "message" => "Internal Server Error",
                "trace" => $th->getTrace()
            ], 500);
        }
    }
}

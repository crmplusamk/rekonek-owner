<?php

namespace Modules\Addon\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureListResource;
use App\Http\Resources\PackageFeatureListResource;
use Illuminate\Http\RedirectResponse;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\resources\AddonListResource;
use Modules\Feature\App\Models\Feature;
use Modules\Feature\App\Repositories\FeatureRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Repositories\PackageRepository;

class AddonApiController extends Controller
{

    public function index(Request $request)
    {
        try {

            $addons = Addon::where('is_active', true)->get();
            $addons = AddonListResource::collection($addons);

            return response()->json([
                "success" => true,
                "message" => "Ok",
                "data" => $addons
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                "error" => true,
                "message" => "Internal Server Error",
                "trace" => $th->getMessage()
            ], 500);
        }
    }
}

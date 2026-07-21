<?php

namespace Modules\DeveloperAccess\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DeveloperAccess\App\Services\DeveloperAccessService;

class DeveloperAccessApiController extends Controller
{

    public $developerAccessService;

    public function __construct(DeveloperAccessService $developerAccessService)
    {
        $this->developerAccessService = $developerAccessService;
    }

    /**
     * Get list of CRM users (client DB) for the developer-access modal select.
     */
    public function getUsers(Request $request)
    {
        try {
            $users = $this->developerAccessService->getClientUsersForSelect();

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $users instanceof \Illuminate\Support\Collection ? $users->values()->all() : $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyBulkByToken(Request $request)
    {
        $request->validate([
            'tokens' => 'required|array|min:1',
            'tokens.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {

            $access = $this->developerAccessService->destroyBulkByToken($request->all());

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $access
            ], 200);

        } catch (\Exception $e) {

            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

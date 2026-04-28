<?php

namespace Modules\DeveloperAccess\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DeveloperAccess\App\Repositories\DeveloperAccessRepository;

class DeveloperAccessApiController extends Controller
{

    public $developerAccessRepo;

    public function __construct(DeveloperAccessRepository $developerAccessRepo)
    {
        $this->developerAccessRepo = $developerAccessRepo;
    }

    /**
     * Get list of CRM users (client DB) for the developer-access modal select.
     */
    public function getUsers(Request $request)
    {
        try {
            $users = $this->developerAccessRepo->getClientUsersForSelect();

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
        DB::beginTransaction();
        try {

            $access = $this->developerAccessRepo->destroyBulkByToken($request->all());

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

<?php

namespace Modules\Deleted\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Deleted\App\Repositories\DeletedCompanyRepository;
use Modules\Customer\App\Repositories\CustomerRepository;

class DeletedCompanyApiController extends Controller
{

    public $deletedCompanyRepo;
    public $customerRepo;

    public function __construct(
        DeletedCompanyRepository $deletedCompanyRepo,
        CustomerRepository $customerRepo)
    {
        $this->deletedCompanyRepo = $deletedCompanyRepo;
        $this->customerRepo = $customerRepo;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $check = $this->deletedCompanyRepo->check($request->company_id);
            if ($check) return response()->json(["error" => true, "message" => "Forbidden"], 403);

            $customer = $this->customerRepo->getByCompanyId($request->company_id);
            $data = $this->deletedCompanyRepo->create([
                "contact_id" => $customer->id,
                "company_id" => $request->company_id,
                "company_name" => $request->company_name,
                "name" => $request->name,
                "email" => $request->email,
                "phone" => $request->phone,
                "is_status" => 0,
                "reason" => $request->reason,
                "note" => null,
                "request_date" => now(),
                "deleted_date" => null,
                "deleted_by" => null,
                "metadata" => json_encode($request['metadata']),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

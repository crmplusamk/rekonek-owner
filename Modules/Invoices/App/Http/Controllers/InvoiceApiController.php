<?php

namespace Modules\Invoices\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Invoices\App\Repositories\InvoiceRepository;

class InvoiceApiController extends Controller
{

    public $invoiceRepo;

    public function __construct(InvoiceRepository $invoiceRepo)
    {
        $this->invoiceRepo = $invoiceRepo;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            /** get invoices */
            $invoices = $this->invoiceRepo->getByCompanyId($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $invoices
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {

            /** get invoice */
            $invoice = $this->invoiceRepo->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $invoice
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

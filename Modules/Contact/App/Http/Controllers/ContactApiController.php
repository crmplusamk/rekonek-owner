<?php

namespace Modules\Contact\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contact\App\Repositories\ContactRepository;
use Modules\Invoices\App\Repositories\InvoiceRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Repositories\PackageRepository;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Subscription\App\Repositories\SubscriptionRepository;

class ContactApiController extends Controller
{

    public $contactRepo;
    public $packageRepo;
    public $subsRepo;
    public $invoiceRepo;
    public $paymentRepo;

    public function __construct(
        ContactRepository $contactRepo,
        PackageRepository $packageRepo,
        SubscriptionRepository $subsRepo,
        InvoiceRepository $invoiceRepo,
        PaymentRepository $paymentRepo)
    {
        $this->contactRepo = $contactRepo;
        $this->packageRepo = $packageRepo;
        $this->subsRepo = $subsRepo;
        $this->invoiceRepo = $invoiceRepo;
        $this->paymentRepo = $paymentRepo;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            /** create customer */
            $customer = $this->contactRepo->create($request->all());

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $customer
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {

            /** create customer */
            $customer = $this->contactRepo->update($request->all());

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $customer
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        DB::beginTransaction();
        try {

            /** verify customer */
            $customer = $this->contactRepo->verify($request->all());

            /** get package */
            $package = $this->packageRepo->getByName("Free");

            /** create subs */
            $subs = $this->subsRepo->create([
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'is_active' => true,
                'started_at' => now(),
                'expired_at' => now()->addDays(14),
                'company_id' => $customer->company_id
            ]);

            /** create invoices */
            $invoice = $this->invoiceRepo->create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'customer_address' => null,
                'company_id' => $customer->company_id,
                'date' => now(),
                'due_date' => now(),
                'tax' => 11,
                'tax_amount' => 0,
                'discount_percentage' => 0,
                'discount_percentage_amount' => 0,
                'discount_amount' => 0,
                'referral_code' => null,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => 0,
                'total' => 0,
                'is_status' => 2,
                'is_paid' => 1,
                'payment_date' => now(),
                'payment_method' => 'Manual Transfer',
                'payment_total' => 0,
                'items' => [
                    [
                        "modelable_id" => $package->id,
                        "modelable_type" => Package::class,
                        "duration" => $package->duration,
                        "duration_type" => $package->duration_type,
                        "quantity" => 1,
                        "charge" => 1,
                        "price" => 0,
                        "subtotal" => 0
                    ]
                ]
            ]);

            /** create payments */
            $this->paymentRepo->create([
                'invoice_id' => $invoice->id,
                'date' => now(),
                'total' => 0,
                'is_status' => 2,
                'note' => null,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $customer
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function activate(Request $request)
    {
        DB::beginTransaction();
        try {

            $customer = $this->contactRepo->activate($request->all());
            $subscription = $this->subsRepo->activate([
                'customer_id' => $customer->id,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => [
                    'customer' => $customer,
                    'subscription' => $subscription
                ],
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

<?php

namespace Modules\Checkout\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Addon\App\Repositories\AddonRepository;
use Modules\Checkout\App\Services\MidtransService;
use Modules\Customer\App\Repositories\CustomerRepository;
use Modules\Invoices\App\Repositories\InvoiceRepository;
use Modules\Logs\App\Services\LogService;
use Modules\Package\App\Repositories\PackageRepository;
use Modules\Package\App\Services\PackageService;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Referral\App\Models\Referral;
use Modules\Referral\App\Models\ReferralUsage;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionService;

class CheckoutApiController extends Controller
{
    public $customerRepo, $packageRepo, $addonRepo, $invoiceRepo, $paymentRepo, $packageSrv, $midtransSrv, $subscriptionSrv;

    public function __construct(CustomerRepository $customerRepo,
        PackageRepository $packageRepo, AddonRepository $addonRepo,
        InvoiceRepository $invoiceRepo, PaymentRepository $paymentRepo,
        PackageService $packageSrv, MidtransService $midtransSrv,
        SubscriptionService $subscriptionSrv)
    {
        $this->customerRepo = $customerRepo;
        $this->packageRepo = $packageRepo;
        $this->addonRepo = $addonRepo;
        $this->invoiceRepo = $invoiceRepo;
        $this->paymentRepo = $paymentRepo;
        $this->packageSrv = $packageSrv;
        $this->midtransSrv = $midtransSrv;
        $this->subscriptionSrv = $subscriptionSrv;
    }

    /**
     * Transaction
     */
    public function packageStore(Request $request)
    {
        DB::beginTransaction();
        try {

            $items = [];
            $subtotal = 0;

            /** verify customer */
            $customer = $this->customerRepo->getByCompanyId($request->company_id);

            /** verify items */
            foreach($request->items as $item)
            {
                $repo = $item['item'] == 'package' ? $this->packageRepo : $this->addonRepo;
                $data = $repo->getById($item['id']);
                if (!$data) break;

                $dataItems = $item['item'] == 'package' ? $this->packageSrv->packageItem($data, $item) : $this->packageSrv->addonItem($data, $item);
                $items[] = $dataItems;
                $subtotal += $dataItems['subtotal'];
            }

            if (count($items) != count($request->items)) return response()->json(["error" => true, "message" => "Not Found"], 404);

            /** calculate price */
            $calculate = $this->packageSrv->calculateTotal($subtotal);
            
            /** process referral code if provided */
            $referralCode = null;
            $referralDiscount = 0;
            $referralId = null;
            
            if ($request->has('referral_code') && $request->referral_code) {
                $referral = Referral::where('code', strtoupper($request->referral_code))->first();
                
                if ($referral && $referral->isAvailable()) {
                    // Check if customer/company can use this referral code
                    if ($referral->canBeUsedBy($customer->id, $request->company_id)) {
                        // Calculate discount based on subtotal before tax
                        $discountBase = $calculate['subtotal'];
                        
                        // Check min purchase requirement
                        if (!$referral->min_purchase || $discountBase >= $referral->min_purchase) {
                            $referralDiscount = $referral->calculateDiscount($discountBase);
                            $referralCode = $referral->code;
                            $referralId = $referral->id;
                        }
                    }
                }
            }
            
            // Apply referral discount to total calculation
            $totalBeforeDiscount = $calculate['total'];
            $finalTotal = max(0, $totalBeforeDiscount - $referralDiscount);

            /** get invoice type from request, default to 'new' for package checkout */
            $invoiceType = $request->input('type', 'new');

            /** create invoices */
            $invoice = $this->invoiceRepo->create([
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'date' => now(),
                'due_date' => now()->addDays(2),
                'tax' => $calculate['tax'],
                'tax_amount' => $calculate['tax_amount'],
                'discount_percentage' => 0,
                'discount_percentage_amount' => 0,
                'discount_amount' => $referralDiscount,
                'referral_code' => $referralCode,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => $calculate['subtotal'],
                'total' => $finalTotal,
                'type' => $invoiceType,
                'is_status' => 1, /** terkonfirmasi */
                'is_paid' => 0, /** belum dibayar */
                'payment_date' => null,
                'payment_method' => null,
                'payment_total' => 0,
                'company_id' => $request->company_id,
                'items' => $items
            ]);

            /** create payment and snap token */
            $setOrder = $this->midtransSrv->setOrder($invoice->code);
            $token = $this->midtransSrv->generateSnapToken(
                $invoice,
                $setOrder->orderId,
                $setOrder->time,
                $setOrder->limit
            );

            $this->paymentStore($invoice, $setOrder, $token);

            LogService::create([
                'fid' => $invoice->id,
                'category' => 'order',
                'title' => 'Membuat Invoice',
                'note' => "Membuat data invoice dengan kode {$invoice->code}",
                'company_id' => $invoice->company_id
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                "data" => [
                    "invoiceId" => $invoice->id,
                    "snapToken" => $token
                ],
            ], 200);

        } catch (\Throwable $th) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function addonStore(Request $request)
    {
        DB::beginTransaction();
        try {

            $items = [];
            $subtotal = 0;

            /** verify customer */
            $customer = $this->customerRepo->getByCompanyId($request->company_id);

            /** verify items */
            foreach($request->items as $item)
            {
                $data = $this->addonRepo->getById($item['id']);
                if (!$data) break;

                $dataItems = $this->packageSrv->addonItem($data, $item);
                $items[] = $dataItems;
                $subtotal += $dataItems['subtotal'];
            }

            if (count($items) != count($request->items)) return response()->json(["error" => true, "message" => "Not Found"], 404);

            /** calculate price */
            $calculate = $this->packageSrv->calculateTotal($subtotal);

            /** get invoice type from request, default to 'addon' for addon checkout */
            $invoiceType = $request->input('type', 'addon');

            /** create invoices */
            $invoice = $this->invoiceRepo->create([
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'date' => now(),
                'due_date' => now()->addDays(2),
                'tax' => $calculate['tax'],
                'tax_amount' => $calculate['tax_amount'],
                'discount_percentage' => 0,
                'discount_percentage_amount' => 0,
                'discount_amount' => 0,
                'referral_code' => null,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => $calculate['subtotal'],
                'total' => $calculate['total'],
                'type' => $invoiceType,
                'is_status' => 1, /** terkonfirmasi */
                'is_paid' => 0, /** belum dibayar */
                'payment_date' => null,
                'payment_method' => null,
                'payment_total' => 0,
                'company_id' => $request->company_id,
                'items' => $items
            ]);

            /** create payment and snap token */
            $setOrder = $this->midtransSrv->setOrder($invoice->code);
            $token = $this->midtransSrv->generateSnapToken(
                $invoice,
                $setOrder->orderId,
                $setOrder->time,
                $setOrder->limit
            );

            $this->paymentStore($invoice, $setOrder, $token);

            LogService::create([
                'fid' => $invoice->id,
                'category' => 'order',
                'title' => 'Membuat Invoice',
                'note' => "Membuat data invoice dengan kode {$invoice->code}",
                'company_id' => $invoice->company_id
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                "data" => [
                    "invoiceId" => $invoice->id,
                    "snapToken" => $token
                ],
            ], 200);

        } catch (\Throwable $th) {

            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getPayment(Request $request)
    {
        try {

            $invoice = $this->invoiceRepo->findUnpaidById($request->invoice_id);
            if (!$invoice) return response()->json(["error" => true, "message" => "Not Found"], 404);

            /** cancel payment */
            if ($invoice->activePayment)
            {
                $now = Carbon::now();
                $dueDate = Carbon::createFromFormat('Y-m-d H:i:s', $invoice->activePayment->due_date);
                if ($now->gt($dueDate)) $this->midtransSrv->cancelPayment([$invoice->activePayment]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                "data" => $invoice,
            ], 200);

        } catch (\Throwable $th) {

            Log::info($th->getMessage());
            Log::info($th->getTraceAsString());

            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getToken(Request $request)
    {
        try {

            $invoice = $this->invoiceRepo->findUnpaidById($request->invoice_id);
            if (!$invoice) return response()->json(["error" => true, "message" => "Not Found"], 404);

            /** cancel invoice payments */
            $payments = $invoice->payments()->whereIn("is_status", [0,1])->get();
            if ($payments) $this->midtransSrv->cancelPayment($payments);

            /** create payment and snap token */
            $setOrder = $this->midtransSrv->setOrder($invoice->code);
            $token = $this->midtransSrv->generateSnapToken(
                $invoice,
                $setOrder->orderId,
                $setOrder->time,
                $setOrder->limit
            );

            $dataPayment = $this->paymentRepo->create([
                'invoice_id' => $invoice->id,
                'order_id' => $setOrder->orderId,
                'date' => now(),
                'due_date' => now()->add($setOrder->time, $setOrder->limit),
                'method' => null,
                'total' => $invoice->total,
                'is_status' => 0,
                'note' => null,
                "metadata" => null,
                'snap_token' => $token
            ]);

            LogService::create([
                'fid' => $dataPayment->id,
                'category' => 'order',
                'title' => 'Membuat Pembayaran',
                'note' => "Membuat data pembayaran untuk invoice {$invoice->code}",
                'company_id' => $invoice->company_id
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ok',
                "data" => [
                    "invoiceId" => $invoice->id,
                    "snapToken" => $token
                ],
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getStatus(Request $request)
    {
        try {

            $detail = $this->midtransSrv->statusPayment($request->orderId);

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                "data" => $detail,
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Callback payment
     */
    public function recurringCallback(Request $request)
    {
        Log::info(json_encode($request->all()));
    }

    public function paymentCallback(Request $request)
    {
        DB::beginTransaction();
        try {

            $validate = $this->midtransSrv->validateSignature([
                "order_id" => $request->order_id,
                "status_code" => $request->status_code,
                "gross_amount" => $request->gross_amount,
                "signature_key" => $request->signature_key
            ]);

            if (!$validate) return response(["error" => true, "message" => "Not Found"], 404);

            $status = $request->transaction_status;
            $orderId = explode("-", $request->order_id);
            $invoice = $this->invoiceRepo->findByCode($orderId[0]);

            if ($status == 'pending') {
                /** pending invoice payment */
                $this->paymentPending($invoice, $request);

            } else if($status == 'capture' || $status == 'settlement') {
                /** done payment */
                $this->paymentSettlement($invoice, $request);

            } else if ($status == 'cancel' || $status == 'deny' || $status == 'expire') {
                /** error payment */
                $this->paymentError($request, $status);

            };

            DB::commit();

        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th->getMessage());
            Log::info($th->getTraceAsString());
        }
    }

    private function paymentPending($invoice, $request)
    {
        $payment = $this->paymentRepo->findByOrderId($request->order_id);

        /** update payments */
        $this->paymentRepo->update($payment, [
            'order_id' => $request->order_id,
            'method' => $request->payment_type,
            'total' => $request->gross_amount,
            'is_status' => 1,
            'note' => null,
            "metadata" => json_encode($request->all()),
        ]);

        LogService::create([
            'fid' => $payment->id,
            'category' => 'order',
            'title' => 'Menunggu Pembayaran',
            'note' => "Pembayaran dengan order id {$payment->order_id}, Menunggu pembayaran",
            'company_id' => $invoice->company_id
        ]);
    }

    private function paymentSettlement($invoice, $request)
    {
        /** get client contact data */
        $client = DB::table('contacts')->where("company_id", $invoice->company_id)->first();

        /** payment date */
        $paymentDate = $request->settlement_time ?? now();

        /** update invoice status */
        $this->invoiceRepo->update($invoice, [
            "is_paid" => 1,
            "is_status" => 2,
            "payment_total" => $invoice->payment_total + $request->gross_amount,
            "payment_method" => $request->payment_type,
            "payment_date" => $paymentDate
        ]);

        /** get payment and update payment status */
        $payment = $this->paymentRepo->findByOrderId($request->order_id);
        $this->paymentRepo->update($payment, [
            "paid_date" => $paymentDate,
            "method" => $request->payment_type,
            "is_status" => 2, /** paid */
            "total" => $request->gross_amount,
            "metadata" => json_encode($request->all()),
        ]);

        LogService::create([
            'fid' => $invoice->id,
            'category' => 'order',
            'title' => 'Status Pembayaran',
            'note' => "Pembayaran dengan order id {$payment->order_id} berhasil dibayar",
            'company_id' => $invoice->company_id
        ]);

        LogService::create([
            'fid' => $invoice->id,
            'category' => 'order',
            'title' => 'Status Invoice',
            'note' => "Invoice dengan kode {$invoice->code} telah dibayar. Order id {$payment->order_id}",
            'company_id' => $invoice->company_id
        ]);

        /** record referral usage if referral code was applied and payment is successful */
        if ($invoice->referral_code && $invoice->discount_amount > 0) {
            $referral = Referral::where('code', $invoice->referral_code)->first();
            
            if ($referral) {
                // Get customer_id from invoice
                $customerId = $invoice->customer_id ?? null;
                
                // Check if usage already recorded (prevent duplicate)
                // Check by invoice_id in metadata to prevent duplicate recording
                $existingUsage = ReferralUsage::where('referral_id', $referral->id)
                    ->where('company_id', $invoice->company_id)
                    ->get()
                    ->filter(function($usage) use ($invoice) {
                        $metadata = json_decode($usage->metadata, true);
                        return isset($metadata['invoice_id']) && $metadata['invoice_id'] === $invoice->id;
                    })
                    ->first();
                
                if (!$existingUsage) {
                    ReferralUsage::create([
                        'referral_id' => $referral->id,
                        'customer_id' => $customerId,
                        'company_id' => $invoice->company_id,
                        'contact_id' => $client->id ?? null,
                        'purchase_amount' => $invoice->subtotal + $invoice->tax_amount, // Total before discount
                        'discount_amount' => $invoice->discount_amount,
                        'metadata' => json_encode([
                            'invoice_id' => $invoice->id,
                            'invoice_code' => $invoice->code,
                            'payment_order_id' => $payment->order_id,
                            'payment_date' => $paymentDate,
                            'created_at' => now()->toDateTimeString(),
                        ]),
                    ]);
                    
                    // Increment used_count
                    Referral::where('id', $referral->id)->increment('used_count');
                    
                    LogService::create([
                        'fid' => $invoice->id,
                        'category' => 'order',
                        'title' => 'Referral Code Used',
                        'note' => "Referral code {$invoice->referral_code} telah digunakan untuk invoice {$invoice->code}",
                        'company_id' => $invoice->company_id
                    ]);
                }
            }
        }

        /** update subscription based on invoice type */
        $invoiceType = $invoice->type ?? 'new';
        $subsPackage = $invoice->items->where('itemable_type', 'Modules\Package\App\Models\Package')->first();
        $subsAddons = $invoice->items->where('itemable_type', 'Modules\Addon\App\Models\Addon')->all();

        /** if type is 'new', delete all existing subscriptions (package + addon) first */
        if ($invoiceType === 'new') {
            // Delete all existing subscription packages
            $deletedPackages = SubscriptionPackage::where('company_id', $invoice->company_id)->delete();
            
            // Delete all existing subscription addons
            $deletedAddons = SubscriptionAddon::where('company_id', $invoice->company_id)->delete();

            LogService::create([
                'fid' => $invoice->id,
                'category' => 'subscription',
                'title' => 'Menghapus Langganan Lama',
                'note' => "Menghapus semua langganan lama (paket: {$deletedPackages}, addon: {$deletedAddons}) untuk invoice {$invoice->code}",
                'company_id' => $invoice->company_id
            ]);
        }

        /** create/update package subscription */
        if ($subsPackage) {
            $dataSubsPackage = $this->subscriptionSrv->updatePackage([
                'customer_id' => $client->id,
                'package_id' => $subsPackage->itemable_id,
                'termin_duration' => $subsPackage->duration,
                'termin' => $subsPackage->duration_type,
                'started_at' => $subsPackage->start_date,
                'expired_at' => $subsPackage->end_date,
                'is_active' => true,
                'company_id' => $invoice->company_id
            ]);

            $logTitle = $invoiceType === 'new' ? 'Membuat Langganan Baru' : 'Upgrade Langganan';
            LogService::create([
                'fid' => $dataSubsPackage->id,
                'category' => 'subscription',
                'title' => $logTitle,
                'note' => "Memperbaharui status langganan ke {$dataSubsPackage->package->name}",
                'company_id' => $invoice->company_id
            ]);
        }

        /** create/update addon subscription */
        if ($subsAddons) {
            foreach($subsAddons as $subsAddon)
            {
                $dataSubsAddon = $this->subscriptionSrv->updateAddon([
                    'customer_id' => $client->id,
                    'addon_id' => $subsAddon->itemable_id,
                    'charge' => $subsAddon->charge,
                    'additional_charge' => $subsAddon->additional_charge ?? 0,
                    'started_at' => $subsAddon->start_date,
                    'expired_at' => $subsAddon->end_date,
                    'is_active' => true,
                    'company_id' => $invoice->company_id
                ]);

                $logTitle = $invoiceType === 'addon' ? 'Menambahkan Addon' : 'Menambahkan Addon (Paket Baru)';
                LogService::create([
                    'fid' => $dataSubsAddon->id,
                    'category' => 'subscription',
                    'title' => $logTitle,
                    'note' => "Menambahkan {$subsAddon->charge} addon {$dataSubsAddon->addon->name}",
                    'company_id' => $invoice->company_id
                ]);
            }
        }
    }

    private function paymentError($request, $status)
    {
        /** get payment and update payment status */
        $payment = $this->paymentRepo->findByOrderId($request->order_id);

        $is_status = [
            'cancel' => [
                'int' => 3,
                'text' => 'dibatalkan'
            ],
            'deny' => [
                'int' => 4,
                'text' => 'ditolak'
            ],
            'expire' => [
                'int' => 5,
                'text' => 'kadaluarsa'
            ],
        ];

        $this->paymentRepo->update($payment, [
            "is_status" => $is_status[$status]['int'] ?? 9,
            "metadata" => json_encode($request->all()),
        ]);

        $logStatus = $is_status[$status]['text'] ?? 'error';
        LogService::create([
            'fid' => $payment->id,
            'category' => 'order',
            'title' => 'Status Pembayaran',
            'note' => "Pembayaran dengan order id {$payment->order_id} telah {$logStatus}",
            'company_id' => $payment->invoice->company_id
        ]);
    }

    /**
     * Helper
     */
    private function paymentStore($invoice, $setOrder, $token)
    {
        $payment = $this->paymentRepo->create([
            'invoice_id' => $invoice->id,
            'order_id' => $setOrder->orderId,
            'date' => now(),
            'due_date' => now()->add($setOrder->time, $setOrder->limit),
            'method' => null,
            'total' => $invoice->total,
            'is_status' => 0,
            'note' => null,
            "metadata" => null,
            'snap_token' => $token
        ]);

        LogService::create([
            'fid' => $payment->id,
            'category' => 'order',
            'title' => 'Membuat Pembayaran',
            'note' => "Membuat data pembayaran untuk invoice {$invoice->code}",
            'company_id' => $payment->invoice->company_id
        ]);

        return $payment;
    }
}

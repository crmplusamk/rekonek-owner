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
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;
use Modules\Invoices\App\Repositories\InvoiceRepository;
use Modules\Logs\App\Services\LogService;
use Modules\Package\App\Repositories\PackageRepository;
use Modules\Package\App\Services\PackageService;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionService;
use App\Jobs\SendThankYouSubscribedMailJob;

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
            $promoDiscount = 0;
            $appliedPromoCode = null;
            if ($request->filled('promo_code')) {
                $promoCodeModel = PromoCode::where('code', $request->promo_code)->first();
                if ($promoCodeModel && $promoCodeModel->isAvailable() && $promoCodeModel->canBeUsedBy($customer->id, $request->company_id)) {
                    $isPerpanjangan = $request->boolean('is_renew');
                    $promoDiscount = $promoCodeModel->calculateDiscountForContext((float) $subtotal, $isPerpanjangan);
                    if ($promoDiscount > 0) {
                        $appliedPromoCode = $promoCodeModel;
                    }
                }
            }

            $totalAfterDiscount = (int) floor($subtotal - $promoDiscount);
            $calculate = $this->packageSrv->calculateTotal($subtotal);
            $calculate['total'] = max(0, $totalAfterDiscount);
            $calculate['subtotal'] = (int) $subtotal;

            /** get invoice type from request, default to 'new' for package checkout */
            $invoiceType = $request->input('type', 'new');

            /** create invoices (promo usage dicatat saat invoice lunas, bukan di sini) */
            $invoicePayload = [
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
                'discount_amount' => $promoDiscount,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => $calculate['subtotal'],
                'total' => $calculate['total'],
                'type' => $invoiceType,
                'is_status' => 1, /** terkonfirmasi */
                'is_paid' => 0,
                'payment_date' => null,
                'payment_method' => null,
                'payment_total' => 0,
                'company_id' => $request->company_id,
                'items' => $items
            ];
            if ($appliedPromoCode) {
                $invoicePayload['promo_code_id'] = $appliedPromoCode->id;
                $invoicePayload['promo_usage_status'] = $request->boolean('is_renew') ? 'P' : 'B'; // P = perpanjangan, B = register/baru
            }
            $invoice = $this->invoiceRepo->create($invoicePayload);

            $totalAmount = (int) $calculate['total'];
            if ($totalAmount <= 0) {
                /** Total 0 (diskon penuh): skip Midtrans, mark invoice lunas & aktifkan langganan; usage dicatat di sini */
                $this->markAsPaidAndActivateSubscription($invoice);
                LogService::create([
                    'fid' => $invoice->id,
                    'category' => 'order',
                    'title' => 'Membuat Invoice',
                    'note' => "Membuat data invoice dengan kode {$invoice->code} (gratis / lunas oleh diskon promo)",
                    'company_id' => $invoice->company_id
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Ok',
                    "data" => [
                        "invoiceId" => $invoice->id,
                        "snapToken" => null,
                        "paidDirectly" => true,
                    ],
                ], 200);
            }

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

            /** calculate price & promo */
            $promoDiscount = 0;
            $appliedPromoCode = null;
            if ($request->filled('promo_code')) {
                $promoCodeModel = PromoCode::where('code', $request->promo_code)->first();
                if ($promoCodeModel && $promoCodeModel->isAvailable() && $promoCodeModel->canBeUsedBy($customer->id, $request->company_id)) {
                    $isPerpanjangan = $request->boolean('is_renew');
                    $promoDiscount = $promoCodeModel->calculateDiscountForContext((float) $subtotal, $isPerpanjangan);
                    if ($promoDiscount > 0) {
                        $appliedPromoCode = $promoCodeModel;
                    }
                }
            }

            $totalAfterDiscount = (int) floor($subtotal - $promoDiscount);
            $calculate = $this->packageSrv->calculateTotal($subtotal);
            $calculate['total'] = max(0, $totalAfterDiscount);
            $calculate['subtotal'] = (int) $subtotal;

            /** get invoice type from request, default to 'addon' for addon checkout */
            $invoiceType = $request->input('type', 'addon');

            /** create invoices (promo usage dicatat saat invoice lunas, bukan di sini) */
            $invoicePayload = [
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
                'discount_amount' => $promoDiscount,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => $calculate['subtotal'],
                'total' => $calculate['total'],
                'type' => $invoiceType,
                'is_status' => 1,
                'is_paid' => 0,
                'payment_date' => null,
                'payment_method' => null,
                'payment_total' => 0,
                'company_id' => $request->company_id,
                'items' => $items
            ];
            if ($appliedPromoCode) {
                $invoicePayload['promo_code_id'] = $appliedPromoCode->id;
                $invoicePayload['promo_usage_status'] = $request->boolean('is_renew') ? 'P' : 'B';
            }
            $invoice = $this->invoiceRepo->create($invoicePayload);

            $totalAmount = (int) $calculate['total'];
            if ($totalAmount <= 0) {
                /** Total 0: mark invoice lunas & aktifkan langganan; usage dicatat di sini */
                $this->markAsPaidAndActivateSubscription($invoice);
                LogService::create([
                    'fid' => $invoice->id,
                    'category' => 'order',
                    'title' => 'Membuat Invoice',
                    'note' => "Membuat data invoice dengan kode {$invoice->code} (gratis / lunas oleh diskon promo)",
                    'company_id' => $invoice->company_id
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Ok',
                    "data" => [
                        "invoiceId" => $invoice->id,
                        "snapToken" => null,
                        "paidDirectly" => true,
                    ],
                ], 200);
            }

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
            $mailPayload = null;

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
                $mailPayload = $this->paymentSettlement($invoice, $request);

            } else if ($status == 'cancel' || $status == 'deny' || $status == 'expire') {
                /** error payment */
                $this->paymentError($request, $status);

            };

            DB::commit();

            if (! empty($mailPayload) && is_array($mailPayload)) {
                SendThankYouSubscribedMailJob::dispatch(
                    $mailPayload['company_id'],
                    $mailPayload['invoice_code'],
                    $mailPayload['payment_date_label'],
                )->onQueue('emails');
            }

        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th->getMessage());
            Log::info($th->getTraceAsString());
        }
    }

    /**
     * Mark invoice as paid (total 0 / gratis) and activate subscription (no Midtrans).
     */
    private function markAsPaidAndActivateSubscription($invoice)
    {
        $paymentDateLabel = Carbon::now()
            ->locale('id')
            ->translatedFormat('d F Y, H:i');

        $this->invoiceRepo->update($invoice, [
            'is_paid' => 1,
            'is_status' => 2,
            'payment_total' => $invoice->total,
            'payment_method' => 'Free',
            'payment_date' => now(),
        ]);

        $client = DB::table('contacts')->where('company_id', $invoice->company_id)->first();
        if (! $client) {
            return;
        }

        $invoice->load('items');
        $invoiceType = $invoice->type ?? 'new';
        $subsPackage = $invoice->items->where('itemable_type', 'Modules\Package\App\Models\Package')->first();
        $subsAddons = $invoice->items->where('itemable_type', 'Modules\Addon\App\Models\Addon');

        if ($invoiceType === 'new') {
            $deletedPackages = SubscriptionPackage::where('company_id', $invoice->company_id)->delete();
            $deletedAddons = SubscriptionAddon::where('company_id', $invoice->company_id)->delete();
            LogService::create([
                'fid' => $invoice->id,
                'category' => 'subscription',
                'title' => 'Menghapus Langganan Lama',
                'note' => "Menghapus langganan lama untuk invoice {$invoice->code} (gratis)",
                'company_id' => $invoice->company_id
            ]);
        }

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
                'note' => "Langganan {$dataSubsPackage->package->name} (gratis / diskon 100%)",
                'company_id' => $invoice->company_id
            ]);
        }

        foreach ($subsAddons as $subsAddon) {
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
                'note' => "Addon {$dataSubsAddon->addon->name} (gratis)",
                'company_id' => $invoice->company_id
            ]);
        }

        LogService::create([
            'fid' => $invoice->id,
            'category' => 'order',
            'title' => 'Invoice Lunas (Gratis)',
            'note' => "Invoice {$invoice->code} lunas oleh diskon promo, langganan telah diaktifkan",
            'company_id' => $invoice->company_id
        ]);

        $this->recordPromoUsageForInvoice($invoice);
        $this->markCompanyRenewOnClient($invoice->company_id);

        return [
            'company_id' => $invoice->company_id,
            'invoice_code' => $invoice->code ?? '',
            'payment_date_label' => $paymentDateLabel,
        ];
    }

    /**
     * Set companies.is_renew = true di database Retalk (client) agar transaksi berikutnya pakai promo perpanjangan.
     */
    private function markCompanyRenewOnClient(?string $companyId): void
    {
        if (! $companyId) {
            return;
        }
        try {
            DB::connection('client')->table('companies')->where('id', $companyId)->update(['is_renew' => true]);
        } catch (\Throwable $e) {
            Log::warning('Checkout: gagal update company is_renew di client DB: '.$e->getMessage());
        }
    }

    /**
     * Catat promo code usage saat invoice lunas (idempotent: satu usage per invoice per promo).
     * Dipanggil dari paymentSettlement (bayar via Midtrans) dan markAsPaidAndActivateSubscription (total 0).
     */
    private function recordPromoUsageForInvoice($invoice): void
    {
        if (! $invoice->promo_code_id) {
            return;
        }

        $source = ($invoice->type ?? 'new') === 'addon' ? 'checkout_addon' : 'checkout_package';
        $usage = PromoCodeUsage::firstOrCreate(
            [
                'promo_code_id' => $invoice->promo_code_id,
                'invoice_id' => $invoice->id,
            ],
            [
                'customer_id' => $invoice->customer_id,
                'company_id' => $invoice->company_id,
                'contact_id' => null,
                'discount_amount' => $invoice->discount_amount,
                'purchase_amount' => $invoice->subtotal,
                'metadata' => ['source' => $source, 'invoice_id' => $invoice->id],
                'is_ref' => false,
                'status' => $invoice->promo_usage_status ?? null,
            ]
        );

        if ($usage->wasRecentlyCreated) {
            PromoCode::where('id', $invoice->promo_code_id)->increment('used_count');
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

    private function paymentSettlement($invoice, $request): ?array
    {
        if (! $invoice) {
            return null;
        }

        /** get client contact data */
        $client = DB::table('contacts')->where("company_id", $invoice->company_id)->first();

        if (! $client) {
            return null;
        }

        /** payment date */
        $paymentDate = $request->settlement_time ?? now();
        $paymentDateLabel = Carbon::parse($paymentDate)
            ->locale('id')
            ->translatedFormat('d F Y, H:i');

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

        $this->recordPromoUsageForInvoice($invoice);
        $this->markCompanyRenewOnClient($invoice->company_id);

        return [
            'company_id' => $invoice->company_id,
            'invoice_code' => $invoice->code ?? '',
            'payment_date_label' => $paymentDateLabel,
        ];
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

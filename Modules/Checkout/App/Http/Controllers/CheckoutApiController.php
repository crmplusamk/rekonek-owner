<?php

namespace Modules\Checkout\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Checkout\App\Exceptions\CheckoutException;
use Modules\Checkout\App\Http\Requests\CheckoutRequest;
use Modules\Checkout\App\Services\CheckoutService;
use Modules\Checkout\App\Services\MidtransService;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;
use Modules\Invoices\App\Services\InvoiceService;
use Modules\Logs\App\Services\LogService;
use Modules\Payment\App\Services\PaymentService;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionService;
use App\Jobs\SendThankYouSubscribedMailJob;

class CheckoutApiController extends Controller
{
    public $invoiceService, $paymentService, $midtransSrv, $subscriptionSrv, $checkoutService;

    public function __construct(
        InvoiceService $invoiceService, PaymentService $paymentService,
        MidtransService $midtransSrv, SubscriptionService $subscriptionSrv,
        CheckoutService $checkoutService)
    {
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->midtransSrv = $midtransSrv;
        $this->subscriptionSrv = $subscriptionSrv;
        $this->checkoutService = $checkoutService;
    }

    /**
     * Checkout terpadu (paket, addon, atau kombinasi).
     * Tiga kondisi dilayani lewat isi items[]; invoice type & gate addon diturunkan
     * server dari komposisi item (lihat CheckoutService::process).
     */
    public function store(CheckoutRequest $request)
    {
        try {
            $result = $this->checkoutService->process([
                'company_id' => $request->input('company_id'),
                'user_id' => $request->input('user_id'),
                'customer' => $request->input('customer', []),
                'items' => $request->input('items', []),
                'promo_code' => $request->input('promo_code'),
                'is_renew' => $request->boolean('is_renew'),
                'payment_channel' => $request->input('payment_channel', 'snap'),
                'bank' => $request->input('bank'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => [
                    'invoice_id' => $result['invoice_id'],
                    'invoice_code' => $result['invoice_code'],
                    'order_id' => $result['order_id'] ?? null,
                    'payment_channel' => $result['payment_channel'] ?? null,
                    'snap_token' => $result['snap_token'],
                    'qris' => $result['qris'] ?? null,
                    'va' => $result['va'] ?? null,
                    'paid_directly' => $result['paid_directly'],
                    'total' => $result['total'],
                ],
            ], 200);

        } catch (CheckoutException $e) {

            return response()->json([
                'success' => false,
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ], $e->getStatus());

        } catch (\Throwable $th) {

            Log::error('Checkout gagal', ['error' => $th->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'INTERNAL_ERROR',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getPayment(Request $request)
    {
        try {

            $invoice = $this->invoiceService->findUnpaidById($request->invoice_id);
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

            $invoice = $this->invoiceService->findUnpaidById($request->invoice_id);
            if (!$invoice) return response()->json(["error" => true, "message" => "Not Found"], 404);
            if ($invoice->due_date && Carbon::parse($invoice->due_date)->isBefore(Carbon::today())) {
                return response()->json(["error" => true, "message" => "Invoice telah expired"], 422);
            }

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

            $dataPayment = $this->paymentService->create([
                'invoice_id' => $invoice->id,
                'order_id' => $setOrder->orderId,
                'date' => now(),
                'due_date' => $setOrder->expiresAt,
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

    public function cancelPayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $payment = $this->paymentService->findById($request->payment_id);
            if (!$payment) return response()->json(["error" => true, "message" => "Not Found"], 404);
            if (! in_array((int) $payment->is_status, [0, 1], true)) {
                return response()->json(["error" => true, "message" => "Payment tidak dapat dibatalkan"], 422);
            }

            if ($payment->method) {
                $this->midtransSrv->cancelOrder($payment->order_id);
            }

            $this->paymentService->update($payment, [
                'is_status' => 3,
                'note' => $payment->method ? 'Dibatalkan dari aplikasi dan Midtrans' : 'Dibatalkan dari aplikasi',
            ]);

            LogService::create([
                'fid' => $payment->id,
                'category' => 'order',
                'title' => 'Status Pembayaran',
                'note' => "Pembayaran dengan order id {$payment->order_id} telah dibatalkan",
                'company_id' => $payment->invoice->company_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment berhasil dibatalkan',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Cancel payment failed', [
                'payment_id' => $request->payment_id,
                'error' => $th->getMessage(),
            ]);

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
            $invoice = $this->invoiceService->findByCode($orderId[0]);

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
     * Dipanggil dari paymentSettlement (bayar via Midtrans). Jalur bebas-biaya (total 0) memakai
     * kembarannya CheckoutService::recordPromoUsage.
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
        $payment = $this->paymentService->findByOrderId($request->order_id);

        /** update payments */
        $this->paymentService->update($payment, [
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
        $this->invoiceService->update($invoice, [
            "is_paid" => 1,
            "is_status" => 2,
            "payment_total" => $invoice->payment_total + $request->gross_amount,
            "payment_method" => $request->payment_type,
            "payment_date" => $paymentDate
        ]);

        /** get payment and update payment status */
        $payment = $this->paymentService->findByOrderId($request->order_id);
        $this->paymentService->update($payment, [
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
                'is_trial' => 'subs',
                // Silent resume dari grace period (jika sebelumnya di-grace).
                'is_grace' => 'active',
                'grace_started_at' => null,
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

            // Prepaid AI Credit: perpanjang expired_at addon aktif ke cycle baru agar saldo
            // carry-over saat perpanjangan (lihat SubscriptionService::extendOneTimeAddonExpiry).
            $this->subscriptionSrv->extendOneTimeAddonExpiry($invoice->company_id, $dataSubsPackage->expired_at);
        }

        /** create/update addon subscription */
        if ($subsAddons) {
            // Get existing subscription for addon expiry alignment
            $existingSubs = SubscriptionPackage::forCompany($invoice->company_id)
                ->currentEffective()
                ->first();

            foreach($subsAddons as $subsAddon)
            {
                $dataSubsAddon = $this->subscriptionSrv->updateAddon([
                    'customer_id' => $client->id,
                    'addon_id' => $subsAddon->itemable_id,
                    'charge' => $subsAddon->charge,
                    'additional_charge' => $subsAddon->additional_charge ?? 0,
                    'started_at' => $subsAddon->start_date,
                    'expired_at' => $existingSubs ? $existingSubs->expired_at : $subsAddon->end_date,
                    'is_active' => true,
                    'company_id' => $invoice->company_id
                ], $invoiceType === 'renew');

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
        $payment = $this->paymentService->findByOrderId($request->order_id);

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

        $this->paymentService->update($payment, [
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
}

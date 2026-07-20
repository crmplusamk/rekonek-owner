<?php

namespace Modules\Checkout\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Addon\App\Services\AddonCrudService;
use Modules\Checkout\App\Exceptions\CheckoutException;
use Modules\Customer\App\Services\CustomerService;
use Modules\Invoices\App\Services\InvoiceService;
use Modules\Logs\App\Services\LogService;
use Modules\Package\App\Services\PackageCrudService;
use Modules\Package\App\Services\PackageService;
use Modules\Payment\App\Services\PaymentService;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionService;

/**
 * Orkestrasi checkout terpadu (paket, addon, atau kombinasi) untuk endpoint POST /api/v1/checkout.
 *
 * Menyerap logika yang dulu terduplikasi di CheckoutApiController::packageStore & addonStore:
 * verifikasi customer -> bangun item -> gate addon -> promo -> buat invoice ->
 * cabang bebas-biaya (aktifkan langsung) / Midtrans (snap token) -> buat payment.
 *
 * Jalur webhook settlement (paymentCallback/paymentSettlement) TETAP di controller dan tidak
 * disentuh. Helper recordPromoUsage/markCompanyRenew sengaja diduplikasi tipis di sini untuk
 * jalur bebas-biaya, mengikuti pola free-path vs webhook yang sudah ada di codebase.
 */
class CheckoutService
{
    public function __construct(
        private CustomerService $customerService,
        private PackageCrudService $packageCrud,
        private AddonCrudService $addonCrud,
        private PackageService $packageSrv,
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private MidtransService $midtransSrv,
        private SubscriptionService $subscriptionSrv,
    ) {
    }

    /**
     * Proses checkout end-to-end. Atomik (DB transaction).
     *
     * @param array $data {
     *   company_id: string, user_id?: string|null,
     *   customer: array{name,email,phone,address},
     *   items: array<array{type,id,quantity,termin,price,duration?,duration_type?}>,
     *   promo_code?: string|null, is_renew?: bool
     * }
     * @return array{invoice_id:string, invoice_code:string, snap_token:?string, paid_directly:bool, total:int}
     *
     * @throws CheckoutException CUSTOMER_NOT_FOUND | ITEM_NOT_FOUND | SUBSCRIPTION_REQUIRED
     */
    public function process(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'];
            $isRenew = (bool) ($data['is_renew'] ?? false);
            $channel = $data['payment_channel'] ?? 'snap'; // 'snap' (Snap redirect) | 'qris' | 'va' (Core API inline)
            $bank = $data['bank'] ?? 'bca';                // bank VA (dipakai bila channel = va)

            /** verifikasi customer */
            $customer = $this->customerService->getByCompanyId($companyId);
            if (! $customer) {
                throw CheckoutException::customerNotFound();
            }

            /** bangun item + tentukan komposisi (ada paket / addon-only) */
            [$items, $subtotal, $hasPackage] = $this->buildItems($data['items']);

            /** gate: pembelian addon-only wajib punya langganan aktif non-trial */
            if (! $hasPackage) {
                $this->assertActiveSubscription($companyId);
            }

            /** invoice type diturunkan dari komposisi item (paket => new, addon-only => addon) */
            $invoiceType = $hasPackage ? 'new' : 'addon';

            /** promo (konteks perpanjangan lewat is_renew) */
            [$promoDiscount, $appliedPromo] = $this->resolvePromo(
                $data['promo_code'] ?? null,
                $customer->id,
                $companyId,
                $subtotal,
                $isRenew
            );

            /** total */
            $calculate = $this->packageSrv->calculateTotal($subtotal);
            $calculate['subtotal'] = (int) $subtotal;
            $calculate['total'] = max(0, (int) floor($subtotal - $promoDiscount));

            /** buat invoice */
            $invoice = $this->invoiceService->create(
                $this->buildInvoicePayload($data, $customer, $items, $calculate, $promoDiscount, $invoiceType, $appliedPromo, $isRenew)
            );

            $total = (int) $calculate['total'];

            if ($total <= 0) {
                /** total 0 (diskon penuh): skip Midtrans, lunasi & aktifkan langganan */
                $this->activateFreeInvoice($invoice);
                LogService::create([
                    'fid' => $invoice->id,
                    'category' => 'order',
                    'title' => 'Membuat Invoice',
                    'note' => "Membuat data invoice dengan kode {$invoice->code} (gratis / lunas oleh diskon promo)",
                    'company_id' => $invoice->company_id,
                ]);

                return [
                    'invoice_id' => $invoice->id,
                    'invoice_code' => $invoice->code,
                    'order_id' => null,
                    'payment_channel' => null,
                    'snap_token' => null,
                    'qris' => null,
                    'va' => null,
                    'paid_directly' => true,
                    'total' => $total,
                ];
            }

            /** buat payment sesuai channel */
            $setOrder = $this->midtransSrv->setOrder($invoice->code);
            $snapToken = null;
            $qris = null;
            $va = null;

            if ($channel === 'qris') {
                // QRIS inline via Core API (bukan Snap). snap_token null; response charge disimpan di metadata.
                $charge = $this->midtransSrv->chargeQris($invoice, $setOrder->orderId);
                $this->storePayment($invoice, $setOrder, null, $charge['raw'], 'qris');
                $qris = [
                    'qr_url' => $charge['qr_url'],
                    'qr_string' => $charge['qr_string'],
                    'expiry_time' => $charge['expiry_time'],
                ];
            } elseif ($channel === 'va') {
                // Virtual Account inline via Core API. snap_token null; response charge disimpan di metadata.
                $charge = $this->midtransSrv->chargeBankTransfer($invoice, $setOrder->orderId, $bank);
                $this->storePayment($invoice, $setOrder, null, $charge['raw'], 'va_' . $bank);
                $va = [
                    'bank' => $charge['bank'],
                    'va_number' => $charge['va_number'],
                    'expiry_time' => $charge['expiry_time'],
                ];
            } else {
                $snapToken = $this->midtransSrv->generateSnapToken(
                    $invoice,
                    $setOrder->orderId,
                    $setOrder->time,
                    $setOrder->limit
                );
                $this->storePayment($invoice, $setOrder, $snapToken);
            }

            LogService::create([
                'fid' => $invoice->id,
                'category' => 'order',
                'title' => 'Membuat Invoice',
                'note' => "Membuat data invoice dengan kode {$invoice->code}",
                'company_id' => $invoice->company_id,
            ]);

            return [
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->code,
                'order_id' => $setOrder->orderId,
                'payment_channel' => $channel,
                'snap_token' => $snapToken,
                'qris' => $qris,
                'va' => $va,
                'paid_directly' => false,
                'total' => $total,
            ];
        });
    }

    /**
     * Bangun baris invoice_items dari payload client dan hitung subtotal.
     *
     * @return array{0: array, 1: float|int, 2: bool} [items, subtotal, hasPackage]
     *
     * @throws CheckoutException ITEM_NOT_FOUND
     */
    private function buildItems(array $rawItems): array
    {
        $items = [];
        $subtotal = 0;
        $hasPackage = false;

        foreach ($rawItems as $raw) {
            $type = $raw['type'];
            $id = $raw['id'];

            $model = $type === 'package' ? $this->findPackage($id) : $this->addonCrud->getById($id);
            if (! $model) {
                throw CheckoutException::itemNotFound($id);
            }

            $normalized = $this->normalizeItem($raw);
            $line = $type === 'package'
                ? $this->packageSrv->packageItem($model, $normalized)
                : $this->packageSrv->addonItem($model, $normalized);

            $items[] = $line;
            $subtotal += $line['subtotal'];
            $hasPackage = $hasPackage || $type === 'package';
        }

        return [$items, $subtotal, $hasPackage];
    }

    /** PackageCrudService::getById memakai findOrFail; bungkus agar id salah -> ITEM_NOT_FOUND, bukan 500. */
    private function findPackage(string $id)
    {
        try {
            return $this->packageCrud->getById($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Lengkapi duration/duration_type bila client tak mengirim (default 1 / mengikuti termin). */
    private function normalizeItem(array $raw): array
    {
        $raw['duration'] = $raw['duration'] ?? 1;
        $raw['duration_type'] = $raw['duration_type'] ?? $raw['termin'];

        return $raw;
    }

    /** @throws CheckoutException SUBSCRIPTION_REQUIRED */
    private function assertActiveSubscription(string $companyId): void
    {
        $active = SubscriptionPackage::forCompany($companyId)
            ->currentEffective()
            ->where('is_trial', 'subs')
            ->first();

        if (! $active) {
            throw CheckoutException::subscriptionRequired();
        }
    }

    /**
     * @return array{0: float|int, 1: PromoCode|null} [discount, appliedPromo]
     */
    private function resolvePromo(?string $promoCode, $customerId, string $companyId, $subtotal, bool $isRenew): array
    {
        if (! $promoCode) {
            return [0, null];
        }

        $model = PromoCode::where('code', $promoCode)->first();
        if ($model && $model->isAvailable() && $model->canBeUsedBy($customerId, $companyId)) {
            $discount = $model->calculateDiscountForContext((float) $subtotal, $isRenew);
            if ($discount > 0) {
                return [$discount, $model];
            }
        }

        return [0, null];
    }

    private function buildInvoicePayload(
        array $data,
        $customer,
        array $items,
        array $calculate,
        $promoDiscount,
        string $invoiceType,
        ?PromoCode $appliedPromo,
        bool $isRenew
    ): array {
        $payload = [
            'customer_id' => $customer->id,
            'customer_name' => $data['customer']['name'] ?? null,
            'customer_email' => $data['customer']['email'] ?? null,
            'customer_phone' => $data['customer']['phone'] ?? null,
            'customer_address' => $data['customer']['address'] ?? null,
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
            'company_id' => $data['company_id'],
            'items' => $items,
        ];

        if ($appliedPromo) {
            $payload['promo_code_id'] = $appliedPromo->id;
            $payload['promo_usage_status'] = $isRenew ? 'P' : 'B'; // P = perpanjangan, B = register/baru
        }

        return $payload;
    }

    /**
     * Total 0 (gratis/diskon penuh): tandai invoice lunas & aktifkan langganan tanpa Midtrans.
     * (Dipindah dari CheckoutApiController::markAsPaidAndActivateSubscription; perilaku identik.)
     */
    private function activateFreeInvoice($invoice): void
    {
        $this->invoiceService->update($invoice, [
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
                'company_id' => $invoice->company_id,
            ]);
            $logTitle = $invoiceType === 'new' ? 'Membuat Langganan Baru' : 'Upgrade Langganan';
            LogService::create([
                'fid' => $dataSubsPackage->id,
                'category' => 'subscription',
                'title' => $logTitle,
                'note' => "Langganan {$dataSubsPackage->package->name} (gratis / diskon 100%)",
                'company_id' => $invoice->company_id,
            ]);

            // Prepaid AI Credit: perpanjang expired_at addon aktif ke cycle baru agar saldo carry-over.
            $this->subscriptionSrv->extendOneTimeAddonExpiry($invoice->company_id, $dataSubsPackage->expired_at);
        }

        foreach ($subsAddons as $subsAddon) {
            $existingSubs = SubscriptionPackage::forCompany($invoice->company_id)
                ->currentEffective()
                ->first();

            $dataSubsAddon = $this->subscriptionSrv->updateAddon([
                'customer_id' => $client->id,
                'addon_id' => $subsAddon->itemable_id,
                'charge' => $subsAddon->charge,
                'additional_charge' => $subsAddon->additional_charge ?? 0,
                'started_at' => $subsAddon->start_date,
                'expired_at' => $existingSubs ? $existingSubs->expired_at : $subsAddon->end_date,
                'is_active' => true,
                'company_id' => $invoice->company_id,
            ], $invoiceType === 'renew');
            $logTitle = $invoiceType === 'addon' ? 'Menambahkan Addon' : 'Menambahkan Addon (Paket Baru)';
            LogService::create([
                'fid' => $dataSubsAddon->id,
                'category' => 'subscription',
                'title' => $logTitle,
                'note' => "Addon {$dataSubsAddon->addon->name} (gratis)",
                'company_id' => $invoice->company_id,
            ]);
        }

        LogService::create([
            'fid' => $invoice->id,
            'category' => 'order',
            'title' => 'Invoice Lunas (Gratis)',
            'note' => "Invoice {$invoice->code} lunas oleh diskon promo, langganan telah diaktifkan",
            'company_id' => $invoice->company_id,
        ]);

        $this->recordPromoUsage($invoice);
        $this->markCompanyRenew($invoice->company_id);
    }

    /**
     * Buat row payment (pending). Snap → `$snapToken` diisi; QRIS/Core API → `$snapToken` null,
     * response charge disimpan di `$metadata` dan `$method` (mis. 'qris').
     * (Dipindah & digeneralisasi dari CheckoutApiController::paymentStore.)
     */
    private function storePayment($invoice, $setOrder, ?string $snapToken = null, $metadata = null, ?string $method = null)
    {
        $payment = $this->paymentService->create([
            'invoice_id' => $invoice->id,
            'order_id' => $setOrder->orderId,
            'date' => now(),
            'due_date' => $setOrder->expiresAt,
            'method' => $method,
            'total' => $invoice->total,
            'is_status' => 0,
            'note' => null,
            'metadata' => $metadata !== null ? json_encode($metadata) : null,
            'snap_token' => $snapToken,
        ]);

        LogService::create([
            'fid' => $payment->id,
            'category' => 'order',
            'title' => 'Membuat Pembayaran',
            'note' => "Membuat data pembayaran untuk invoice {$invoice->code}",
            'company_id' => $payment->invoice->company_id,
        ]);

        return $payment;
    }

    /**
     * Catat promo code usage saat invoice lunas (idempotent: satu usage per invoice per promo).
     * Kembaran dari CheckoutApiController::recordPromoUsageForInvoice (dipakai jalur webhook);
     * di sini khusus jalur bebas-biaya.
     */
    private function recordPromoUsage($invoice): void
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

    /**
     * Set companies.is_renew = true di database app (koneksi client) agar transaksi berikutnya
     * memakai promo perpanjangan. Kembaran dari CheckoutApiController::markCompanyRenewOnClient.
     */
    private function markCompanyRenew(?string $companyId): void
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
}

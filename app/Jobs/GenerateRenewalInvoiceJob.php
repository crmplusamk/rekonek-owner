<?php

namespace App\Jobs;

use App\Jobs\SendInvoicePaymentReminderJob;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Customer\App\Services\CustomerService;
use Modules\Invoices\App\Services\InvoiceService;
use Modules\Package\App\Services\PackageService;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\RenewalQuoteService;

class GenerateRenewalInvoiceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $uniqueFor = 86400;

    public function __construct(
        public string $subscriptionPackageId,
        public string $companyId,
    ) {}

    public function uniqueId(): string
    {
        return 'renew_invoice_' . $this->companyId . '_' . now()->toDateString();
    }

    public function handle(
        CustomerService $customerService,
        InvoiceService $invoiceService,
        PackageService $packageSrv,
    ): void {
        try {
            DB::beginTransaction();

            $subsPackage = SubscriptionPackage::with(['package', 'customer'])
                ->find($this->subscriptionPackageId);

            if (!$subsPackage) {
                Log::warning('GenerateRenewalInvoiceJob: Subscription package not found', [
                    'subscription_package_id' => $this->subscriptionPackageId,
                ]);
                DB::rollBack();
                return;
            }

            /** Cek apakah invoice renew sudah ada (dalam 7 hari terakhir) */
            $existingInvoice = DB::table('invoices')
                ->where('company_id', $subsPackage->company_id)
                ->where('type', 'renew')
                ->where('is_paid', 0)
                ->where('is_status', 1)
                ->whereDate('date', '>=', Carbon::now()->subDays(7))
                ->first();

            if ($existingInvoice) {
                Log::info('GenerateRenewalInvoiceJob: Invoice already exists, skipping', [
                    'company_id' => $subsPackage->company_id,
                    'invoice_code' => $existingInvoice->code,
                ]);
                DB::rollBack();
                return;
            }

            /** Ambil customer */
            $customer = $customerService->getByCompanyId($subsPackage->company_id);
            if (!$customer) {
                Log::error('GenerateRenewalInvoiceJob: Customer not found', [
                    'company_id' => $subsPackage->company_id,
                ]);
                DB::rollBack();
                return;
            }

            /** Hitung item & total via sumber kebenaran tunggal (RenewalQuoteService) */
            $quote = app(RenewalQuoteService::class)->quoteForSubscription($subsPackage);

            if (!$quote || empty($quote['items'])) {
                Log::error('GenerateRenewalInvoiceJob: Quote/package not found', [
                    'subscription_package_id' => $subsPackage->id,
                ]);
                DB::rollBack();
                return;
            }

            $items = $quote['items'];
            $subtotal = $quote['subtotal'];

            /** Calculate & create invoice */
            $calculate = $packageSrv->calculateTotal($subtotal);

            $invoice = $invoiceService->create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name ?? 'Customer',
                'customer_email' => $customer->email ?? '',
                'customer_phone' => $customer->phone ?? '',
                'customer_address' => $customer->address ?? '',
                'date' => now(),
                'due_date' => Carbon::parse($subsPackage->expired_at),
                'tax' => $calculate['tax'],
                'tax_amount' => $calculate['tax_amount'],
                'discount_percentage' => 0,
                'discount_percentage_amount' => 0,
                'discount_amount' => 0,
                'admin_fee' => 0,
                'service_fee' => 0,
                'subtotal' => $calculate['subtotal'],
                'total' => $calculate['total'],
                'type' => 'renew',
                'is_status' => 1,
                'is_paid' => 0,
                'payment_date' => null,
                'payment_method' => null,
                'payment_total' => 0,
                'company_id' => $subsPackage->company_id,
                'items' => $items,
            ]);

            DB::commit();

            $expiredDateLabel = Carbon::parse($subsPackage->expired_at)->locale('id')->isoFormat('D MMMM YYYY');
            $dueDateLabel = Carbon::parse($invoice->due_date)->locale('id')->isoFormat('D MMMM YYYY');
            $totalFormatted = 'Rp ' . number_format($calculate['total'], 0, ',', '.');

            SendInvoicePaymentReminderJob::dispatch(
                $subsPackage->company_id,
                $invoice->code,
                $totalFormatted,
                $expiredDateLabel,
                $dueDateLabel,
            );

            Log::info('GenerateRenewalInvoiceJob: Invoice created', [
                'invoice_code' => $invoice->code,
                'company_id' => $subsPackage->company_id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GenerateRenewalInvoiceJob failed', [
                'subscription_package_id' => $this->subscriptionPackageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

}

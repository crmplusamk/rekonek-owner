<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Addon\App\Models\Addon;
use Modules\Customer\App\Repositories\CustomerRepository;
use Modules\Invoices\App\Repositories\InvoiceRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Services\PackageService;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

class GenerateInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:generate-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate renewal invoices 1 week before subscription expires';

    protected $customerRepo;
    protected $invoiceRepo;
    protected $packageSrv;

    public function __construct(
        CustomerRepository $customerRepo,
        InvoiceRepository $invoiceRepo,
        PackageService $packageSrv
    ) {
        parent::__construct();
        $this->customerRepo = $customerRepo;
        $this->invoiceRepo = $invoiceRepo;
        $this->packageSrv = $packageSrv;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation for renewals...');

        // Calculate date 1 week from now
        $oneWeekFromNow = Carbon::now()->addWeek()->format('Y-m-d');

        // Find all active subscription packages that expire in 1 week
        $subscriptionPackages = SubscriptionPackage::where('is_active', true)
            ->whereDate('expired_at', $oneWeekFromNow)
            ->with(['package', 'customer'])
            ->get();

        $this->info("Found {$subscriptionPackages->count()} subscription packages to renew.");

        foreach ($subscriptionPackages as $subsPackage) {
            try {
                DB::beginTransaction();

                // Check if invoice already exists for this renewal (within last 7 days)
                $existingInvoice = DB::table('invoices')
                    ->where('company_id', $subsPackage->company_id)
                    ->where('type', 'renew')
                    ->where('is_paid', 0)
                    ->where('is_status', 1)
                    ->whereDate('date', '>=', Carbon::now()->subDays(7))
                    ->first();

                if ($existingInvoice) {
                    $this->warn("Invoice already exists for company {$subsPackage->company_id} (Invoice: {$existingInvoice->code}), skipping...");
                    DB::rollBack();
                    continue;
                }

                // Get customer
                $customer = $this->customerRepo->getByCompanyId($subsPackage->company_id);
                if (!$customer) {
                    $this->error("Customer not found for company {$subsPackage->company_id}, skipping...");
                    DB::rollBack();
                    continue;
                }

                $items = [];
                $subtotal = 0;

                // Add package to items
                $package = $subsPackage->package;
                if (!$package) {
                    $this->error("Package not found for subscription {$subsPackage->id}, skipping...");
                    DB::rollBack();
                    continue;
                }

                $termin = $subsPackage->termin ?? 'month';
                $terminDuration = $subsPackage->termin_duration ?? 1;
                
                // Normalize termin value
                if ($termin === 'monthly') $termin = 'month';
                if ($termin === 'yearly') $termin = 'year';
                
                // Calculate package price based on termin
                $packagePrice = $this->calculatePackagePrice($package->price, $termin);
                
                $packageItem = [
                    'modelable_id' => $package->id,
                    'modelable_type' => Package::class,
                    'duration' => $terminDuration,
                    'duration_type' => $termin,
                    'termin' => $termin,
                    'termin_duration' => $terminDuration,
                    'quantity' => 1,
                    'price' => $packagePrice,
                    'total' => $packagePrice,
                    'start_date' => $subsPackage->expired_at, // Start from current expiry
                    'end_date' => Carbon::parse($subsPackage->expired_at)
                        ->add($terminDuration, $termin . 's')
                        ->format('Y-m-d H:i:s'),
                ];

                $items[] = $packageItem;
                $subtotal += $packagePrice;

                // Get all active addons for this company that expire around the same time
                $subscriptionAddons = SubscriptionAddon::where('company_id', $subsPackage->company_id)
                    ->where('is_active', true)
                    ->whereDate('expired_at', '>=', Carbon::now())
                    ->with('addon')
                    ->get();

                // Add addons to items (use same termin as package)
                foreach ($subscriptionAddons as $subsAddon) {
                    $addon = $subsAddon->addon;
                    if (!$addon) continue;

                    // Use package termin for addon pricing
                    $addonPrice = $this->calculateAddonPrice($addon->price, $termin);
                    
                    // Get quantity from subscription addon charge
                    // charge represents how many units of the addon
                    $quantity = max(1, $subsAddon->charge ?? 1);
                    
                    $addonItem = [
                        'modelable_id' => $addon->id,
                        'modelable_type' => Addon::class,
                        'duration' => $terminDuration,
                        'duration_type' => $termin,
                        'termin' => $termin,
                        'termin_duration' => $terminDuration,
                        'quantity' => $quantity,
                        'charge' => $subsAddon->charge ?? 1,
                        'price' => $addonPrice,
                        'total' => $addonPrice * $quantity,
                        'start_date' => $subsPackage->expired_at, // Start from package expiry
                        'end_date' => Carbon::parse($subsPackage->expired_at)
                            ->add($terminDuration, $termin . 's')
                            ->format('Y-m-d H:i:s'),
                    ];

                    $items[] = $addonItem;
                    $subtotal += $addonPrice * $quantity;
                }

                if (empty($items)) {
                    $this->warn("No items to invoice for company {$subsPackage->company_id}, skipping...");
                    DB::rollBack();
                    continue;
                }

                // Calculate totals
                $calculate = $this->packageSrv->calculateTotal($subtotal);

                // Create invoice
                $invoice = $this->invoiceRepo->create([
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name ?? 'Customer',
                    'customer_email' => $customer->email ?? '',
                    'customer_phone' => $customer->phone ?? '',
                    'customer_address' => $customer->address ?? '',
                    'date' => now(),
                    'due_date' => now()->addDays(2),
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
                    'is_status' => 1, // confirmed
                    'is_paid' => 0, // unpaid
                    'payment_date' => null,
                    'payment_method' => null,
                    'payment_total' => 0,
                    'company_id' => $subsPackage->company_id,
                    'items' => $items
                ]);

                $this->info("Invoice {$invoice->code} created for company {$subsPackage->company_id}");

                DB::commit();

            } catch (\Throwable $th) {
                DB::rollBack();
                $this->error("Error creating invoice for company {$subsPackage->company_id}: " . $th->getMessage());
            }
        }

        $this->info('Invoice generation completed.');
    }

    /**
     * Calculate package price based on termin
     */
    private function calculatePackagePrice($monthlyPrice, $termin)
    {
        if ($termin === 'month' || $termin === 'monthly') {
            return $monthlyPrice;
        }

        // Yearly: calculate with 20% discount
        $yearlyTotal = $monthlyPrice * 12;
        $discount = ($yearlyTotal * 20) / 100;
        $yearlyWithDiscount = $yearlyTotal - $discount;
        
        // Round down to nearest thousand
        return floor($yearlyWithDiscount / 1000) * 1000;
    }

    /**
     * Calculate addon price based on termin
     */
    private function calculateAddonPrice($monthlyPrice, $termin)
    {
        if ($termin === 'month' || $termin === 'monthly') {
            return $monthlyPrice;
        }

        // Yearly: multiply monthly price by 12 (no discount for addon)
        return $monthlyPrice * 12;
    }
}

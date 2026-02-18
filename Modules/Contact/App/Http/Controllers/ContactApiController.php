<?php

namespace Modules\Contact\App\Http\Controllers;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contact\App\Repositories\ContactRepository;
use Modules\Invoices\App\Repositories\InvoiceRepository;
use Modules\Package\App\Models\Package;
use Modules\Package\App\Repositories\PackageRepository;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Subscription\App\Repositories\SubscriptionRepository;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\PromoCode\App\Models\PromoCodeUsage;
use App\Services\AccessLogService;

class ContactApiController extends Controller
{
    public $contactRepo;

    public $packageRepo;

    public $subsRepo;

    public $invoiceRepo;

    public $paymentRepo;

    protected AccessLogService $accessLogService;

    public function __construct(
        ContactRepository $contactRepo,
        PackageRepository $packageRepo,
        SubscriptionRepository $subsRepo,
        InvoiceRepository $invoiceRepo,
        PaymentRepository $paymentRepo,
        AccessLogService $accessLogService)
    {
        $this->contactRepo = $contactRepo;
        $this->packageRepo = $packageRepo;
        $this->subsRepo = $subsRepo;
        $this->invoiceRepo = $invoiceRepo;
        $this->paymentRepo = $paymentRepo;
        $this->accessLogService = $accessLogService;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $customer = $this->contactRepo->create($request->all());

            // Handle promo code usage if provided (from ref query param or manual input)
            if ($request->promo_code) {
                $promoCode = PromoCode::where('code', $request->promo_code)->first();
                
                if ($promoCode && $promoCode->isAvailable()) {
                    PromoCodeUsage::create([
                        'promo_code_id' => $promoCode->id,
                        'customer_id' => null, // Not yet a customer, will be updated after activation
                        'company_id' => $customer->company_id,
                        'contact_id' => $customer->id,
                        'discount_amount' => null,
                        'purchase_amount' => null,
                        'metadata' => [
                            'source' => 'registration',
                            'email' => $customer->email,
                        ],
                        'is_ref' => true, // Mark as referral/promo from registration
                    ]);

                    // Increment promo code usage count
                    $promoCode->increment('used_count');
                }
            }

            $this->accessLogService->create([
                'email' => $request->email ?? null,
                'progress' => 'registration_success',
                'category' => 'registration',
                'number' => $request->phone ?? null,
                'company_id' => $customer->company_id ?? null,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => 200,
                'request_data' => [
                    'email' => $request->email ?? null,
                    'number' => $request->phone ?? null,
                    'company_id' => $request->company_id ?? null,
                    'promo_code' => $request->promo_code ?? null,
                ],
                'action' => 'register',
                'activity_type' => 'account_registration',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $customer,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
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
                'data' => $customer,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
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
            $package = $this->packageRepo->getByName('Free');

            /** create subs */
            $subs = $this->subsRepo->create([
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'is_active' => true,
                'started_at' => now(),
                'expired_at' => now()->addDays(14),
                'company_id' => $customer->company_id,
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
                        'modelable_id' => $package->id,
                        'modelable_type' => Package::class,
                        'duration' => $package->duration,
                        'duration_type' => $package->duration_type,
                        'quantity' => 1,
                        'charge' => 1,
                        'price' => 0,
                        'subtotal' => 0,
                    ],
                ],
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

            // Send greeting message to customer after successful email verification
            $this->sendGreetingMessage($customer->phone, $customer->name ?? 'Customer');

            return response()->json([
                'success' => true,
                'message' => 'Ok',
                'data' => $customer,
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send greeting message to customer after successful email verification
     */
    private function sendGreetingMessage($phone, $customerName)
    {
        try {

            $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();

            if (! $session) {
                return false;
            }

            $message = "Halo Kak *{$customerName}*! 👋\n\n".
                       "Terima kasih sudah memilih Rekonek untuk jadi pusat komando bisnis Anda. Akun Anda sudah siap!\n\n".
                       "Agar tidak bingung, yuk tonton video panduan setup 2 menit ini:\n".
                       "https://www.youtube.com/watch?v=u063lZ-zDGQ\n\n".
                       "*Langkah pertama Anda:*\n\n".
                       "1. Login ke Dashboard:\n".
                       "https://app.rekonek.com/login\n\n".
                       "2. Hubungkan WhatsApp (Scan QR)\n\n".
                       "3. Atur akses tim Anda.\n\n".
                       "Selamat tinggal blindspot 🚀\n\n".
                       '_Ini adalah pesan otomatis dari Rekonek_';

            $response = WhatsappHelper::sendTextMessage(
                $session->session,
                $phone,
                $message
            );

            return $response;

        } catch (\Exception $e) {

            \Log::error('Failed to send greeting message: '.$e->getMessage());

            return false;
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
                    'subscription' => $subscription,
                ],
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

use App\Models\Addon;
use App\Models\Contact;
use App\Models\Feature;
use Illuminate\Support\Str;
// use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionFeature;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\AddonListResource;
use App\Http\Resources\AuthServiceResource;
use App\Http\Resources\ContactListResource;
use App\Http\Resources\FeatureListResource;
use App\Http\Resources\PackageListResource;
use App\Http\Resources\PackageFeatureListResource;
use Modules\Package\App\Models\Package;
use Modules\Subscription\App\Models\Subscription;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\WhatsappOtp\App\Http\Controllers\WebhookOtpController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('chat-whatsapp-webhook-event/v2/private/{session}/{userId}', [WebhookOtpController::class, 'handle']);

Route::get('authentication/{companyid}', function ($companyid) {

    // return response()->json([
    //     'data' => $companyid
    // ], 200);

    try {

        $subsPackage = SubscriptionPackage::where('company_id', $companyid)
            ->with(["package.features.addon.subscriptionAddons" => function($query) use($companyid) {
                $query->where([
                    'is_active' => true,
                    'company_id' => $companyid
                ]);
            }])
            ->first();

        $subsAddon = SubscriptionAddon::where('company_id', $companyid)
            ->with('addon.feature')
            ->get();

        return new AuthServiceResource([
            'subsPackage' => $subsPackage,
            'subsAddon' => $subsAddon
        ]);

    } catch (\Exception $e) {

        return response()->json([
            "error" => true,
            "message" => $e->getMessage()
        ], 500);
    }
});

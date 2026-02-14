<?php

// use App\Models\Subscription;
use App\Http\Controllers\Api\AccessLogApiController;
use App\Http\Resources\AuthServiceResource;
use Illuminate\Support\Facades\Route;
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

Route::post('access-logs', [AccessLogApiController::class, 'store']);

Route::get('authentication/{companyid}', function ($companyid) {

    // return response()->json([
    //     'data' => $companyid
    // ], 200);

    try {

        $subsPackage = SubscriptionPackage::where('company_id', $companyid)
            ->with(['package.features.addon.subscriptionAddons' => function ($query) use ($companyid) {
                $query->where([
                    'is_active' => true,
                    'company_id' => $companyid,
                ]);
            }])
            ->first();

        $subsAddon = SubscriptionAddon::where('company_id', $companyid)
            ->with('addon.feature')
            ->get();

        return new AuthServiceResource([
            'subsPackage' => $subsPackage,
            'subsAddon' => $subsAddon,
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

use Modules\Subscription\App\Http\Controllers\SubscriptionApiController;

Route::prefix('v1')->name('api.')->group(function () {
    /** proyeksi tagihan perpanjangan (sumber kebenaran "Nilai Langganan" di app) */
    Route::get('subscription/{companyId}/renewal-quote', [SubscriptionApiController::class, 'renewalQuote'])
        ->name('subscription.renewal-quote');
});

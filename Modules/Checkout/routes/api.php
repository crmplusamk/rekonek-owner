<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Checkout\App\Http\Controllers\CheckoutApiController;

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

Route::prefix('v1')->name('api.')->group(function () {

    Route::post('checkout/package', [CheckoutApiController::class, 'packageStore'])->name('checkout.package.store');
    Route::post('checkout/addon', [CheckoutApiController::class, 'addonStore'])->name('checkout.addon.store');
});

/** midtrans route get snap token */
Route::prefix('v1/midtrans')->name('api.midtrans.')->group(function () {

    Route::get('payment/get-payment', [CheckoutApiController::class, 'getPayment'])->name('payment.get-payment');
    Route::get('payment/get-status', [CheckoutApiController::class, 'getStatus'])->name('payment.get-status');
    Route::get('payment/get-token', [CheckoutApiController::class, 'getToken'])->name('payment.get-token');
});

/** midtrans route webhook callback */
Route::prefix('v1/midtrans/webhook')->name('api.midtrans.webhook.')->group(function () {

    Route::post('payment/callback', [CheckoutApiController::class, 'paymentCallback'])->name('payment.callback');
    Route::post('recurring/callback', [CheckoutApiController::class, 'recurringCallback'])->name('recurring.callback');
});

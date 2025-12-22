<?php

use Illuminate\Support\Facades\Route;
use Modules\Voucher\App\Http\Controllers\Api\VoucherApiController;

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

Route::prefix('v1/voucher')->name('api.voucher.')->group(function () {
    // Public endpoints (no auth required for checking vouchers)
    Route::post('check', [VoucherApiController::class, 'check'])->name('check');
    Route::post('calculate', [VoucherApiController::class, 'calculate'])->name('calculate');
    Route::get('available', [VoucherApiController::class, 'available'])->name('available');
    
    // Protected endpoints (require auth for usage tracking)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('use', [VoucherApiController::class, 'use'])->name('use');
    });
});

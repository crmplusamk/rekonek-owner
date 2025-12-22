<?php

use Illuminate\Support\Facades\Route;
use Modules\Referral\App\Http\Controllers\Api\ReferralApiController;

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

Route::prefix('v1/referral')->name('api.referral.')->group(function () {
    // Public endpoints (no auth required for checking referral codes)
    Route::post('check', [ReferralApiController::class, 'check'])->name('check');
    Route::post('calculate', [ReferralApiController::class, 'calculate'])->name('calculate');
    Route::get('available', [ReferralApiController::class, 'available'])->name('available');
    
    // Protected endpoints (require auth for usage tracking)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('use', [ReferralApiController::class, 'use'])->name('use');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\PromoCode\App\Http\Controllers\Api\PromoCodeApiController;

Route::prefix('v1/promo-code')->name('api.promo-code.')->group(function () {
    Route::post('check', [PromoCodeApiController::class, 'check'])->name('check');
    Route::post('calculate', [PromoCodeApiController::class, 'calculate'])->name('calculate');
    Route::get('available', [PromoCodeApiController::class, 'available'])->name('available');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('use', [PromoCodeApiController::class, 'use'])->name('use');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\PromoCode\App\Http\Controllers\PromoCodeController;

Route::middleware('auth')->group(function () {
    Route::get('promo-code/get-datatable', [PromoCodeController::class, 'datatable'])->name('promo-code.table');
    Route::get('promo-code/status/{id}', [PromoCodeController::class, 'status'])->name('promo-code.status');
    Route::resource('promo-code', PromoCodeController::class)->names('promo-code')->except(['show']);
});

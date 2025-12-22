<?php

use Illuminate\Support\Facades\Route;
use Modules\Referral\App\Http\Controllers\ReferralController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('referral/get-datatable', [ReferralController::class, 'datatable'])->name('referral.table');
    Route::get('referral/status/{id}', [ReferralController::class, 'status'])->name('referral.status');
    Route::resource('referral', ReferralController::class)->names('referral');
});

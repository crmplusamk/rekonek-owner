<?php

use Illuminate\Support\Facades\Route;
use Modules\Verification\App\Http\Controllers\VerificationController;

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

    Route::get('verification/otp-sender/get-datatable', [VerificationController::class, 'otpSenderDatatable'])->name('verification.otpsender.table');

    Route::get('verification/create-session', [VerificationController::class, 'createSession'])->name('verification.create.session');
    Route::get('verification/subscribe-session', [VerificationController::class, 'subscribeSession'])->name('verification.subscribe.session');

    Route::resource('verification', VerificationController::class)->names('verification');
});


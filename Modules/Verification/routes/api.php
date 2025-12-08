<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Verification\App\Http\Controllers\VerificationApiController;

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
    Route::post('verification/send-token', [VerificationApiController::class, 'sendTokenVerification'])->name('verification.send.token');
    Route::post('verification/token/verify', [VerificationApiController::class, 'tokenVerify'])->name('verification.token.verify');
});

<?php

use Illuminate\Support\Facades\Route;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | OTP verification API (send-token, token/verify) removed - registration
    | no longer uses phone/OTP. Module kept for RegistrationToken model used by OtpHistory.
    |
*/

Route::prefix('v1')->name('api.')->group(function () {
    // Legacy OTP registration endpoints removed
});

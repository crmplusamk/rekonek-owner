<?php

use Illuminate\Support\Facades\Route;
use Modules\OtpHistory\App\Http\Controllers\OtpHistoryController;

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
    Route::get('otphistory/get-datatable', [OtpHistoryController::class, 'datatable'])->name('otphistory.table');
    Route::resource('otphistory', OtpHistoryController::class)->names('otphistory');
});

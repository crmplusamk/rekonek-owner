<?php

use Illuminate\Support\Facades\Route;
use Modules\Voucher\App\Http\Controllers\VoucherController;

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

Route::group([], function () {
    Route::resource('voucher', VoucherController::class)->names('voucher');
});

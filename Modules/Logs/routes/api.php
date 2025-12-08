<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Logs\App\Http\Controllers\LogsApiController;

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
    Route::get('log/subscription', [LogsApiController::class, 'logSubData'])->name('log.sub.data');
    Route::get('log/order', [LogsApiController::class, 'logOrderData'])->name('log.order.data');
});

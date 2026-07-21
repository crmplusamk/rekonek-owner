<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use Modules\Addon\App\Http\Controllers\AddonPriceApiController;

Route::prefix('v1')->name('api.')->group(function () {
    /** quote harga addon (normal vs diskon tier) untuk ditampilkan app */
    Route::get('addon-price', [AddonPriceApiController::class, 'price'])->name('addon.price');
});

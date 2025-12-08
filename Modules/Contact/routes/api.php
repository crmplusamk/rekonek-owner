<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Contact\App\Http\Controllers\ContactApiController;

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
    Route::post('contact/create', [ContactApiController::class, 'store'])->name('contact.store');
    Route::post('contact/update', [ContactApiController::class, 'update'])->name('contact.update');
    Route::post('contact/verify', [ContactApiController::class, 'verify'])->name('contact.verify');
});

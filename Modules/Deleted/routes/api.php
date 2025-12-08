<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Deleted\App\Http\Controllers\DeletedCompanyApiController;

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

Route::prefix('v1')->name('api.')->group(function ()
{
    Route::post('delete-company-account', [DeletedCompanyApiController::class, 'store'])->name('deleted-account.store');
});

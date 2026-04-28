<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\DeveloperAccess\App\Http\Controllers\DeveloperAccessApiController;

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
    Route::get('developer-access/users', [DeveloperAccessApiController::class, 'getUsers'])->name('developer-access.users');
    Route::post('developer-access/delete-bulk-by-token', [DeveloperAccessApiController::class, 'destroyBulkByToken'])->name('developer-access.delete-token-bulk');
});

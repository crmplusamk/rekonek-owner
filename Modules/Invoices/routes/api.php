<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Invoices\App\Http\Controllers\InvoiceApiController;

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
    Route::get('invoices', [InvoiceApiController::class, 'index'])->name('invoice.index');
    Route::get('invoice/{id}/show', [InvoiceApiController::class, 'show'])->name('invoice.show');
});

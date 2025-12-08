<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\App\Http\Controllers\CustomerController;

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
    Route::get('customer/get-list', [CustomerController::class, 'list'])->name('customer.list');
    Route::get('customer/get-datatable', [CustomerController::class, 'datatable'])->name('customer.table');
    Route::get('customer/status/{id}', [CustomerController::class, 'status'])->name('customer.status');

    Route::resource('customer', CustomerController::class)->names('customer');
});

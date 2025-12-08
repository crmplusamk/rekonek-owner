<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\App\Http\Controllers\SubscriptionController;

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
    Route::get('subscription/get-datatable', [SubscriptionController::class, 'datatable'])->name('subscription.table');
    Route::get('subscription/status/{id}', [SubscriptionController::class, 'status'])->name('subscription.status');

    Route::resource('subscription', SubscriptionController::class)->names('subscription');
});

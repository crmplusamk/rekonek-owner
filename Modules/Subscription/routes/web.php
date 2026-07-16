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

    // Override manual / reset baris aturan snapshot (bukan mengubah record subscription).
    Route::put('subscription/{id}/rules/{feature}', [SubscriptionController::class, 'updateRule'])->name('subscription.rules.update');
    Route::post('subscription/{id}/rules/{feature}/reset', [SubscriptionController::class, 'resetRule'])->name('subscription.rules.reset');

    // Subscription view-only: hanya daftar & detail (tanpa CRUD admin).
    Route::resource('subscription', SubscriptionController::class)->names('subscription')->only(['index', 'show']);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Addon\App\Http\Controllers\AddonController;

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
    Route::get('addon/get-datatable', [AddonController::class, 'datatable'])->name('addon.table');
    Route::get('addon/status/{id}', [AddonController::class, 'status'])->name('addon.status');

    Route::resource('addon', AddonController::class)->names('addon');
});


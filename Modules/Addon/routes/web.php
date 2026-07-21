<?php

use Illuminate\Support\Facades\Route;
use Modules\Addon\App\Http\Controllers\AddonController;
use Modules\Addon\App\Http\Controllers\AddonPriceTierController;

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

    // CRUD addon lewat modal di halaman index (tanpa halaman create/show/edit terpisah).
    Route::resource('addon', AddonController::class)->names('addon')->except(['create', 'show', 'edit']);

    // Aturan Diskon (Price Tier) per addon — halaman kelola satu addon.
    Route::get('addon/{addon}/tier', [AddonPriceTierController::class, 'index'])->name('addon.tier.index');
    Route::post('addon/{addon}/tier', [AddonPriceTierController::class, 'store'])->name('addon.tier.store');
    Route::put('addon/tier/{id}', [AddonPriceTierController::class, 'update'])->name('addon.tier.update');
    Route::get('addon/tier/status/{id}', [AddonPriceTierController::class, 'status'])->name('addon.tier.status');
    Route::delete('addon/tier/{id}', [AddonPriceTierController::class, 'destroy'])->name('addon.tier.destroy');
});


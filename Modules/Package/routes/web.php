<?php

use Illuminate\Support\Facades\Route;
use Modules\Package\App\Http\Controllers\PackageController;

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
    Route::get('package/list', [PackageController::class, 'list'])->name('package.list');
    Route::get('package/get-datatable', [PackageController::class, 'datatable'])->name('package.table');
    Route::get('package/status/{id}', [PackageController::class, 'status'])->name('package.status');

    Route::resource('package', PackageController::class)->names('package');
});


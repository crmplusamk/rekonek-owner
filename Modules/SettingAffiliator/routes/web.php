<?php

use Illuminate\Support\Facades\Route;
use Modules\SettingAffiliator\App\Http\Controllers\SettingAffiliatorController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('setting-affiliator', [SettingAffiliatorController::class, 'index'])
        ->name('setting-affiliator.index');
    Route::get('setting-affiliator/get-datatable', [SettingAffiliatorController::class, 'datatable'])
        ->name('setting-affiliator.table');
    Route::get('setting-affiliator/{id}/usage', [SettingAffiliatorController::class, 'usage'])
        ->name('setting-affiliator.usage');
    Route::post('setting-affiliator', [SettingAffiliatorController::class, 'store'])
        ->name('setting-affiliator.store');
    Route::put('setting-affiliator/{id}', [SettingAffiliatorController::class, 'update'])
        ->name('setting-affiliator.update');
    Route::delete('setting-affiliator/{id}', [SettingAffiliatorController::class, 'destroy'])
        ->name('setting-affiliator.destroy');
    Route::get('setting-affiliator/{id}/config', [SettingAffiliatorController::class, 'getConfig'])
        ->name('setting-affiliator.config');
    Route::put('setting-affiliator/{id}/config', [SettingAffiliatorController::class, 'saveConfig'])
        ->name('setting-affiliator.config.update');
});

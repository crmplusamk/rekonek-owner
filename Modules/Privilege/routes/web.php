<?php

use Illuminate\Support\Facades\Route;
use Modules\Privilege\App\Http\Controllers\PermissionController;
use Modules\Privilege\App\Http\Controllers\RoleController;

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

Route::middleware('auth')->group(function ()
{
    Route::get('role/get-datatable', [RoleController::class, 'datatable'])->name('role.table');
    Route::get('role/status/{id}', [RoleController::class, 'status'])->name('role.status');

    Route::resource('role', RoleController::class)->names('role');

    Route::resource('permission', PermissionController::class)->names('permission');
});

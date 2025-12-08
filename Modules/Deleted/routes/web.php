<?php

use Illuminate\Support\Facades\Route;
use Modules\Deleted\App\Http\Controllers\DeletedCompanyController;

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
    Route::get('delete-company-account', [DeletedCompanyController::class, 'index'])->name('delete-company-account.index');
});


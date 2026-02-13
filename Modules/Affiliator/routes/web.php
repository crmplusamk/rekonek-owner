<?php

use Illuminate\Support\Facades\Route;
use Modules\Affiliator\App\Http\Controllers\AffiliatorController;

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

Route::group(['middleware' => ['auth', 'affiliator']], function () {
    Route::resource('affiliator', AffiliatorController::class)->names('affiliator');
});

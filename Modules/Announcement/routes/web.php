<?php

use Illuminate\Support\Facades\Route;
use Modules\Announcement\App\Http\Controllers\AnnouncementController;

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
    Route::get('announcement/get-datatable', [AnnouncementController::class, 'datatable'])->name('announcement.table');
    Route::get('announcement/get-companies', [AnnouncementController::class, 'companies'])->name('announcement.companies');
    Route::resource('announcement', AnnouncementController::class)->names('announcement')->except(['show']);
});

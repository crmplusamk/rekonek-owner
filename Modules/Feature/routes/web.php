<?php

use Illuminate\Support\Facades\Route;
use Modules\Feature\App\Http\Controllers\FeatureCategoryController;
use Modules\Feature\App\Http\Controllers\FeatureController;

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
    Route::get('feature/get-datatable', [FeatureController::class, 'datatable'])->name('feature.table');
    Route::resource('feature', FeatureController::class)->names('feature');

    Route::get('feature-category/get-datatable', [FeatureCategoryController::class, 'datatable'])->name('feature.category.table');
    Route::get('feature-category/get-list', [FeatureCategoryController::class, 'list'])->name('feature.category.list');

    Route::resource('feature-category', FeatureCategoryController::class)->names('feature.category');
});

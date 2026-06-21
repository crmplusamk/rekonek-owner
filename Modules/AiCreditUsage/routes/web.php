<?php

use Illuminate\Support\Facades\Route;
use Modules\AiCreditUsage\App\Http\Controllers\AiCreditUsageController;

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
    Route::get('ai-credit-usage', [AiCreditUsageController::class, 'index'])->name('ai-credit-usage.index');
    Route::get('ai-credit-usage/summary', [AiCreditUsageController::class, 'summary'])->name('ai-credit-usage.summary');
    Route::get('ai-credit-usage/get-datatable', [AiCreditUsageController::class, 'datatable'])->name('ai-credit-usage.table');

    Route::get('ai-credit-usage/company/{company}', [AiCreditUsageController::class, 'show'])->name('ai-credit-usage.show');
    Route::get('ai-credit-usage/company/{company}/summary', [AiCreditUsageController::class, 'companySummary'])->name('ai-credit-usage.company.summary');
    Route::get('ai-credit-usage/company/{company}/get-datatable', [AiCreditUsageController::class, 'companyDatatable'])->name('ai-credit-usage.company.table');
});

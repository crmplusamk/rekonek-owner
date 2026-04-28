<?php

use App\Jobs\DeleteCompanyDataJob;
use App\Jobs\DeleteCompanyMongoDataJob;
use App\Models\Contact;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Invoices\App\Models\Invoice;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/testing/company-purge', function (Request $request) {
    $companyId = trim((string) $request->query('company_id', ''));

    if ($companyId === '') {
        return response()->json([
            'message' => 'company_id query param is required.',
        ], 422);
    }

    DeleteCompanyDataJob::dispatch($companyId);
    DeleteCompanyMongoDataJob::dispatch($companyId);

    return response()->json([
        'message'    => 'Company purge jobs have been queued (postgres + mongo).',
        'company_id' => $companyId,
        'queues'     => [
            'postgres' => 'company_data_purge',
            'mongo'    => 'company_data_purge_mongo',
        ],
    ], 202);
});



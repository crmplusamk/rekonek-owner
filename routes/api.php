<?php

// use App\Models\Subscription;
use Illuminate\Support\Facades\Route;
use Modules\WhatsappOtp\App\Http\Controllers\WebhookOtpController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('chat-whatsapp-webhook-event/v2/private/{session}/{userId}', [WebhookOtpController::class, 'handle']);
Route::post('chat-whatsapp-webhook-event/v3/private/{session}/{userId}', [WebhookOtpController::class, 'handle']);

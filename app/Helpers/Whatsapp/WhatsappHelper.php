<?php

namespace App\Helpers\Whatsapp;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class WhatsappHelper
{
    public static function createSession()
    {
        $user = Auth::user();
        $instance  = 'otp-session-' . Str::random(5);
        $headers  = [
            'apikey' => config('apigateway.WHATSAPP_SERVICE_V2_URL_API_KEY'),
            'Content-Type'  => 'application/json'
        ];

        $endpoint = config('apigateway.WHATSAPP_SERVICE_V2_URL_CREATE_INSTANCE');
        $request  = [
            'instanceName' => $instance,
            'qrcode' => true,
            'syncFullHistory' => false,
            'groupsIgnore' => true,
            'integration' => 'WHATSAPP-BAILEYS',
            'webhook' => [
                'url' => config('webhook.WEBHOOK_WHATSAPP_SERVICE_V2_URL') . '/' . $instance . '/' .$user->id,
                'base64' => false,
                'events' => [
                    "APPLICATION_STARTUP",
                    "QRCODE_UPDATED",
                    "CONNECTION_UPDATE",
                    "LOGOUT_INSTANCE",
                    "REMOVE_INSTANCE",
                    "SEND_MESSAGE"
                ]
            ]
        ];

        $response = Http::withHeaders($headers)->post($endpoint, $request);

        if (isset($response->json()['qrcode'])) {

            WhatsappOtpSession::create([
                'session' => $instance,
                'status' => 0,
            ]);

            return [
                'instance' => $instance,
                'qrcode' => $response->json()['qrcode']
            ];
        }

        return false;
    }

    public static function sendTextMessage($sessionCode, $contactNumber, $message)
    {
        $headers  = [
            'apikey' => config('apigateway.WHATSAPP_SERVICE_V2_URL_API_KEY'),
            'Content-Type' => 'application/json'
        ];
        $endpoint = config('apigateway.WHATSAPP_SERVICE_V2_URL_SEND_TEXT_MESSAGE') . '/' . $sessionCode;
        $request  = [
            'number' => $contactNumber,
            'text' => $message,
        ];

        $response = Http::withHeaders($headers)->post($endpoint, $request);
        $data = $response->json();

        if ($data['status'] == 400 || $data['status'] == 404 || $data['status'] == 500) {
            return false;
        }

        return $data;
    }
}

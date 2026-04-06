<?php

namespace App\Helpers\Whatsapp\V2;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappHelper
{
    /**
     * Hapus instance Evolution API / WhatsApp service dan set chat_sessions.status = 0 di DB client (CRM).
     */
    public static function disconnectInstance(string $instanceCode): array
    {
        $headers = [
            'apikey' => config('apigateway.WHATSAPP_SERVICE_V2_URL_API_KEY'),
            'Content-Type' => 'application/json',
        ];

        $endpoint = config('apigateway.WHATSAPP_SERVICE_V2_URL_DELETE_INSTANCE');

        try {
            $response = Http::withHeaders($headers)->delete($endpoint.'/'.$instanceCode);
            $json = $response->json() ?? [];

            if (isset($json['status']) && $json['status'] == 'SUCCESS') {
                DB::connection('client')
                    ->table('chat_sessions')
                    ->where('code', $instanceCode)
                    ->where('channel', 'whatsapp')
                    ->update([
                        'status' => 0,
                        'updated_at' => now(),
                    ]);

                return [
                    'status' => 'success',
                    'message' => 'WhatsApp session disconnected',
                ];
            }

            Log::warning('Whatsapp V2 disconnect: API did not return SUCCESS', [
                'code' => $instanceCode,
                'response' => $json,
            ]);

            return [
                'status' => 'error',
                'message' => 'WhatsApp session failed to disconnect',
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('Whatsapp V2 disconnect exception: '.$e->getMessage(), [
                'code' => $instanceCode,
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}

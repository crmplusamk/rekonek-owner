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

        return self::createWahaSession($instance, $user);
    }

    private static function createWahaSession(string $instance, $user)
    {
        $baseUrl = rtrim((string) config('chat.services.waha.base_url'), '/');
        $token = (string) config('chat.services.waha.token');
        $timeout = (int) config('chat.services.waha.timeout', 30);
        $webhookBase = rtrim((string) config('chat.services.waha.webhook_base_url'), '/');
        if ($webhookBase === '') {
            return [
                'error' => true,
                'message' => 'CHAT_WAHA_WEBHOOK_BASE_URL kosong.',
            ];
        }
        if ($baseUrl === '') {
            Log::warning('WAHA base url is empty');
            return [
                'error' => true,
                'message' => 'CHAT_WAHA_BASE_URL kosong.',
            ];
        }

        $baseHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $headers = $baseHeaders + ['X-Api-Key' => $token];

        $createPayload = [
            'name' => $instance,
            'start' => false,
            'config' => [
                'metadata' => [
                    'user.id' => (string) ($user->id ?? ''),
                    'user.email' => (string) ($user->email ?? ''),
                ],
                'webhooks' => [
                    [
                        'url' => $webhookBase !== '' ? $webhookBase.'/'.$instance.'/'.($user->id ?? '') : null,
                        'events' => [
                            'session.status',
                            'message',
                        ],
                        'retries' => [
                            'delaySeconds' => 2,
                            'attempts' => 5,
                            'policy' => 'linear',
                        ],
                    ],
                ],
            ],
        ];

        $createResponse = Http::withHeaders($headers)
            ->timeout($timeout)
            ->post($baseUrl.'/api/sessions', $createPayload);

        if (! $createResponse->successful()) {
            Log::warning('WAHA create session failed', [
                'instance' => $instance,
                'status' => $createResponse->status(),
                'response' => $createResponse->json(),
            ]);
            return [
                'error' => true,
                'message' => 'WAHA create session gagal.',
                'meta' => [
                    'status' => $createResponse->status(),
                    'response' => $createResponse->json(),
                ],
            ];
        }

        // WAHA biasanya membuat session dalam status STOPPED.
        // Pastikan session di-start dulu sebelum meminta QR.
        $startResponse = Http::withHeaders($headers)
            ->timeout($timeout)
            ->post($baseUrl.'/api/sessions/'.$instance.'/start');

        if (! $startResponse->successful()) {
            Log::warning('WAHA start session failed', [
                'instance' => $instance,
                'status' => $startResponse->status(),
                'response' => $startResponse->json(),
            ]);
            return [
                'error' => true,
                'message' => 'WAHA start session gagal.',
                'meta' => [
                    'status' => $startResponse->status(),
                    'response' => $startResponse->json(),
                ],
            ];
        }

        $qrResponse = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $qrResponse = Http::withHeaders($headers)
                ->timeout($timeout)
                ->get($baseUrl.'/api/'.$instance.'/auth/qr', [
                    'format' => 'image',
                ]);

            if (! $qrResponse->successful()) {
                $qrResponse = Http::withHeaders($headers)
                    ->timeout($timeout)
                    ->get($baseUrl.'/api/'.$instance.'/auth/qr', [
                        'format' => 'raw',
                    ]);
            }

            if ($qrResponse->successful() && ! empty(data_get($qrResponse->json(), 'data'))) {
                break;
            }

            usleep(500000); // 0.5 detik
        }

        if (! $qrResponse || ! $qrResponse->successful()) {
            Log::warning('WAHA get qr failed', [
                'instance' => $instance,
                'status' => $qrResponse?->status(),
                'response' => $qrResponse?->json(),
            ]);
            return [
                'error' => true,
                'message' => 'WAHA get QR gagal.',
                'meta' => [
                    'status' => $qrResponse?->status(),
                    'response' => $qrResponse?->json(),
                ],
            ];
        }

        $qr = $qrResponse->json();
        $base64 = (string) ($qr['data'] ?? '');
        $mimeType = (string) ($qr['mimetype'] ?? 'image/png');

        if ($base64 === '') {
            return [
                'error' => true,
                'message' => 'WAHA get QR berhasil tapi data QR kosong.',
                'meta' => [
                    'response' => $qr,
                ],
            ];
        }

        WhatsappOtpSession::create([
            'session' => $instance,
            'status' => 0,
            'created_by' => $user->id ?? null,
        ]);

        return [
            'instance' => $instance,
            'qrcode' => [
                'base64' => str_starts_with($base64, 'data:') ? $base64 : 'data:'.$mimeType.';base64,'.$base64,
            ],
        ];
    }

    public static function sendTextMessage($sessionCode, $contactNumber, $message)
    {
        $baseUrl = rtrim((string) config('chat.services.waha.base_url'), '/');
        $token = (string) config('chat.services.waha.token');
        $timeout = (int) config('chat.services.waha.timeout', 30);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Api-Key' => $token,
        ];

        $chatId = self::normalizeChatId($contactNumber);
        $request = [
            'session' => $sessionCode,
            'chatId' => $chatId,
            'id' => null,
            'reply_to' => null,
            'text' => $message,
            'linkPreview' => true,
            'linkPreviewHighQuality' => false,
        ];

        $response = Http::withHeaders($headers)
            ->timeout($timeout)
            ->post($baseUrl.'/api/sendText', $request);
        $data = $response->json();

        if (! $response->successful() || empty(data_get($data, 'id'))) {
            Log::warning('WAHA send text failed', [
                'session' => $sessionCode,
                'chat_id' => $chatId,
                'status' => $response->status(),
                'response' => $data,
            ]);
            return [
                'error' => true,
                'data' => $data
            ];
        }

        return [
            'error' => false,
            'data' => $data
        ];
    }

    public static function disconnectSession(string $sessionCode): array
    {
        $baseUrl = rtrim((string) config('chat.services.waha.base_url'), '/');
        $token = (string) config('chat.services.waha.token');
        $timeout = (int) config('chat.services.waha.timeout', 30);

        if ($baseUrl === '') {
            Log::warning('WAHA base url is empty on disconnect session', [
                'session' => $sessionCode,
            ]);

            return [
                'error' => true,
                'message' => 'CHAT_WAHA_BASE_URL kosong.',
            ];
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Api-Key' => $token,
        ];

        $steps = [
            [
                'name' => 'stop',
                'method' => 'post',
                'url' => $baseUrl.'/api/sessions/'.$sessionCode.'/stop',
            ],
            [
                'name' => 'delete',
                'method' => 'delete',
                'url' => $baseUrl.'/api/sessions/'.$sessionCode,
            ],
        ];

        $results = [];

        foreach ($steps as $step) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout($timeout)
                    ->send($step['method'], $step['url']);

                $body = $response->json();
                $successful = $response->successful() || in_array($response->status(), [404, 409], true);

                $results[] = [
                    'step' => $step['name'],
                    'status' => $response->status(),
                    'success' => $successful,
                    'response' => $body,
                ];

                if (! $successful) {
                    Log::warning('WAHA disconnect session step failed', [
                        'session' => $sessionCode,
                        'step' => $step['name'],
                        'status' => $response->status(),
                        'response' => $body,
                    ]);
                }
            } catch (\Throwable $th) {
                Log::warning('WAHA disconnect session request exception', [
                    'session' => $sessionCode,
                    'step' => $step['name'],
                    'message' => $th->getMessage(),
                ]);

                $results[] = [
                    'step' => $step['name'],
                    'success' => false,
                    'message' => $th->getMessage(),
                ];
            }
        }

        $hasSuccessfulStep = collect($results)->contains(function ($result) {
            return ($result['success'] ?? false) === true;
        });

        return [
            'error' => ! $hasSuccessfulStep,
            'message' => $hasSuccessfulStep
                ? 'WAHA session disconnect attempted.'
                : 'Semua request disconnect WAHA gagal.',
            'data' => $results,
        ];
    }

    private static function normalizeChatId($contactNumber): string
    {
        $raw = trim((string) $contactNumber);
        if ($raw === '') {
            return '@c.us';
        }

        if (str_contains($raw, '@')) {
            return $raw;
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits !== '' && str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return ($digits !== '' ? $digits : $raw).'@c.us';
    }
}

<?php

namespace Modules\Verification\App\Http\Controllers;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Http\Controllers\Controller;
use App\Services\AccessLogService;
use Illuminate\Http\Request;
use Modules\Verification\App\Models\RegistrationToken;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class VerificationApiController extends Controller
{
    protected AccessLogService $accessLogService;

    public function __construct(AccessLogService $accessLogService)
    {
        $this->accessLogService = $accessLogService;
    }

    public function sendTokenVerification(Request $request)
    {
        try {

            $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();
            $token = $this->generateOtpToken(4);

            $response = WhatsappHelper::sendTextMessage(
                $session->session,
                $request->number,
                "*Verifikasi Rekonek*\n\n".
                "Hai! Kode verifikasi kamu adalah *{$token}*\n".
                "Jangan berikan kode ini kepada siapapun ya!\n\n".
                '_Ini adalah pesan otomatis dari Rekonek_'
            );

            /**
             * Response status
             */
            if ($response == false) {
                return response()->json(['error' => true, 'message' => 'Terjadi kesalahan'], 500);
            }

            RegistrationToken::create([
                'email' => $request->email,
                'sender' => $session->number,
                'receiver' => $request->number,
                'token' => $token,
                'status' => false,
            ]);

            // $this->accessLogService->create([
            //     'email' => $request->email,
            //     'number' => $request->number,
            //     'progress' => 'request_token',
            //     'category' => 'verification',
            //     'method' => $request->method(),
            //     'endpoint' => $request->path(),
            //     'status_code' => 200,
            //     'request_data' => [
            //         'email' => $request->email,
            //         'number' => $request->number,
            //     ],
            //     'action' => 'send_token',
            //     'activity_type' => 'request_token',
            // ]);

            return response()->json(['success' => true, 'message' => 'ok'], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateOtpToken($length)
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    public function tokenVerify(Request $request)
    {
        $check = null;

        try {
            $check = RegistrationToken::where([
                'receiver' => $request->number,
                'status' => false,
            ])
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $check || $check->token != $request->code) {
                $this->accessLogService->create([
                    'number' => $request->number,
                    'progress' => 'token_verification_failed',
                    'category' => 'verification',
                    'email' => $check->email ?? null,
                    'method' => $request->method(),
                    'endpoint' => $request->path(),
                    'status_code' => 404,
                    'request_data' => [
                        'number' => $request->number,
                        'code' => $request->code,
                    ],
                    'action' => 'verify_token',
                    'activity_type' => 'token_verification',
                ]);

                return response()->json(['success' => true, 'message' => 'Not Found'], 404);
            }

            $check->update([
                'status' => true,
            ]);

            $this->accessLogService->create([
                'email' => $check->email ?? null,
                'number' => $request->number,
                'progress' => 'token_verified',
                'category' => 'verification',
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => 200,
                'request_data' => [
                    'number' => $request->number,
                    'code' => $request->code,
                ],
                'action' => 'verify_token',
                'activity_type' => 'token_verification',
            ]);

            return response()->json(['success' => true, 'message' => 'ok'], 200);

        } catch (\Exception $e) {
            $this->accessLogService->create([
                'number' => $request->number ?? null,
                'progress' => 'token_verification_error',
                'category' => 'verification',
                'email' => $check->email ?? null,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => 500,
                'request_data' => [
                    'number' => $request->number ?? null,
                    'code' => $request->code ?? null,
                ],
                'action' => 'verify_token',
                'activity_type' => 'token_verification',
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getTrace(),
            ], 500);
        }
    }
}

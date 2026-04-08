<?php

namespace Modules\Verification\App\Http\Controllers;

use App\Helpers\Whatsapp\WhatsappHelper;
use App\Http\Controllers\Controller;
use App\Services\AccessLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            if ($response['error'] == true) {
                return response()->json(['error' => true, 'message' => 'Terjadi kesalahan', 'data' => $response], 500);
            }

            RegistrationToken::create([
                'email' => Str::lower(trim((string) $request->input('email', ''))),
                'sender' => $session->number,
                'receiver' => $this->normalizePhoneNumber($request->number),
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
        return str_pad((string) random_int(0, (int) pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Normalize nomor untuk query (digit saja, konsisten saat simpan & verify).
     * Format Indonesia: 08xxx → 628xxx agar sama dengan format WhatsApp.
     */
    private function normalizePhoneNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return (string) $value;
        }
        if (strlen($digits) >= 10 && str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Normalize kode token dari input: trim, ambil digit saja, pad 4 karakter (leading zero).
     */
    private function normalizeTokenCode($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) trim((string) $value));

        return str_pad($digits, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Bandingkan token tersimpan dengan kode input (keduanya dinormalisasi).
     */
    private function tokenMatches(?string $storedToken, string $normalizedCode): bool
    {
        if ($storedToken === null || $storedToken === '') {
            return false;
        }
        $stored = $this->normalizeTokenCode($storedToken);

        return $stored === $normalizedCode;
    }

    public function tokenVerify(Request $request)
    {
        $check = null;

        try {
            $receiver = $this->normalizePhoneNumber($request->number);
            $code = $this->normalizeTokenCode($request->code);

            $check = RegistrationToken::where([
                'receiver' => $receiver,
                'status' => false,
            ])
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $check || $this->tokenMatches($check->token, $code) === false) 
            {
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

            return response()->json([
                'error' => true,
                'message' => $e->getTrace(),
            ], 500);
        }
    }
}

<?php

namespace Modules\Verification\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Helpers\Whatsapp\WhatsappHelper;
use Modules\Verification\App\Models\RegistrationToken;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class VerificationApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function sendTokenVerification(Request $request)
    {
        try {

            $session = WhatsappOtpSession::where('status', true)->orderBy('created_at', 'asc')->first();
            $token = $this->generateOtpToken(4);

            $response = WhatsappHelper::sendTextMessage(
                $session->session,
                $request->number,
                "🔐 *Verifikasi CRM Plus*\n\n" .
                "Hai! Kode verifikasi kamu adalah *{$token}*\n" .
                "Jangan berikan kode ini kepada siapapun ya!"
            );

            /**
             * Response status
             */
            if ($response == false) return response()->json(['error' => true, 'message' => 'Terjadi kesalahan'], 500);

            RegistrationToken::create([
                'email' => $request->email,
                'sender' => $session->number,
                'receiver' => $request->number,
                'token' => $token,
                'status' => false
            ]);

            return response()->json(['success' => true, 'message' => 'ok'], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    function generateOtpToken($length)
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    function tokenVerify(Request $request)
    {
        try {

            $check = RegistrationToken::where([
                'receiver' => $request->number,
                'status' => false,
            ])
            ->orderBy('created_at', 'desc')
            ->first();

            if ($check->token != $request->code) return response()->json(['success' => true, 'message' => 'Not Found'], 404);

            $check->update([
                'status' => true
            ]);

            return response()->json(['success' => true, 'message' => 'ok'], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace Modules\WhatsappOtp\App\Http\Controllers;

use App\Helpers\NotificationSender;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

class WebhookOtpController extends Controller
{
    private $response;
    private $session;
    private $user;

    public function handle(Request $request, $session, $userId)
    {
        $payload = $request->all();
        $this->response = isset($payload[0]) && is_array($payload[0]) ? $payload[0] : $payload;
        $this->session = $session;
        $this->user = DB::table('users')->find($userId);
        Log::channel('whatsapp')->info('WebhookOtp received', [
            'session' => $this->session,
            'user_id' => $userId,
            'event' => data_get($this->response, 'event'),
            'status' => data_get($this->response, 'payload.status'),
        ]);

        $this->sessionStatusUpdate();
        $this->connectionOpen();
        $this->connectionClose();

        return response()->json(['status' => 'ok'], 200);
    }

    private function sessionStatusUpdate()
    {
        try {
            $isWahaStatus = isset($this->response['event']) && $this->response['event'] === 'session.status';
            $wahaStatus = strtoupper((string) data_get($this->response, 'payload.status', ''));
            $wahaQr = (string) data_get($this->response, 'payload.qr', '');
            if ($isWahaStatus && in_array($wahaStatus, ['SCAN_QR', 'SCAN_QR_CODE'], true) && $wahaQr !== '') {
                $base64QrCode = str_starts_with($wahaQr, 'data:')
                    ? $wahaQr
                    : 'data:image/png;base64,'.$wahaQr;
                $data = (object) [
                    'uuid' => Str::uuid(),
                    'context' => 'whatsapp-otp',
                    'title' => 'Request Code QR',
                    'process' => 'qr-code',
                    'status' => 'success',
                    'notify' => true,
                    'message' => "Whatsapp OTP, kode QR baru telah digenerate",
                    'data' => $base64QrCode,
                ];

                $this->notifyPrivate($data);
            }

        } catch (\Throwable $th) {

            Log::channel('whatsapp')->error('Error function connectionUpdate', [
                'message' => $th->getMessage()
            ]);

            return false;
        }
    }

    private function connectionOpen()
    {
        try {
            $isWahaStatus = isset($this->response['event']) && $this->response['event'] === 'session.status';
            $wahaStatus = strtoupper((string) data_get($this->response, 'payload.status', ''));
            if ($isWahaStatus && in_array($wahaStatus, ['WORKING', 'CONNECTED'], true)) {
                $scannedNumber = (string) data_get($this->response, 'me.id', data_get($this->response, 'payload.me.id', ''));
                if ($scannedNumber !== '' && str_contains($scannedNumber, '@')) {
                    $scannedNumber = explode('@', $scannedNumber)[0];
                }

                $session = WhatsappOtpSession::where('session', $this->session)->update([
                    'status' => 1,
                    'number' => $scannedNumber !== '' ? $scannedNumber : null,
                ]);

                $data = (object) [
                    'uuid' => Str::uuid(),
                    'context' => 'whatsapp-otp',
                    'title' => 'Open',
                    'process' => 'whatsapp-open',
                    'status' => 'success',
                    'notify' => true,
                    'message' => "Whatsapp, Terhubung",
                    'data' => $session,
                ];

                $this->notifyPrivate($data);
                return true;
            }

        } catch (\Throwable $th) {

            Log::channel('whatsapp')->error('Error function connectionOpen', [
                'message' => $th
            ]);

            return false;
        }
    }

    private function connectionClose()
    {
        try {
            $isWahaStatus = isset($this->response['event']) && $this->response['event'] === 'session.status';
            $wahaStatus = strtoupper((string) data_get($this->response, 'payload.status', ''));
            if ($isWahaStatus && in_array($wahaStatus, ['FAILED', 'STOPPED', 'CLOSED'], true)) {
                $session = WhatsappOtpSession::where('session', $this->session)->delete();

                $data = (object) [
                    'uuid' => Str::uuid(),
                    'context' => 'whatsapp-otp',
                    'title' => 'Close',
                    'process' => 'whatsapp-close',
                    'status' => 'success',
                    'notify' => true,
                    'message' => "Whatsapp, Terputus",
                    'data' => $session,
                ];

                $this->notifyPrivate($data);
                return true;
            }

        } catch (\Throwable $th) {

            Log::channel('whatsapp')->error('Error function connectionClose', [
                'message' => $th
            ]);

            return false;
        }
    }

    private function notifyPrivate(object $data): void
    {
        try {
            if (! $this->user) {
                return;
            }
            NotificationSender::send($data)->toPrivate(true, false, $this->user);
        } catch (\Throwable $th) {
            Log::channel('whatsapp')->error('Error function notifyPrivate', [
                'message' => $th->getMessage(),
            ]);
        }
    }
}

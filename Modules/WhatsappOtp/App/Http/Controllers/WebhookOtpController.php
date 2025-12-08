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
        $this->response = array($request->all())[0];
        $this->session = $session;
        $this->user = DB::table('users')->find($userId);

        $this->connectionUpdate();
        $this->connectionOpen();
        $this->connectionClose();
        $this->connectionLoss();
    }

    private function connectionUpdate()
    {
        try {

            if (isset($this->response['event']) && $this->response['event'] == 'qrcode.updated') {

                $base64QrCode = $this->response['data']['qrcode']['base64'];
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

                NotificationSender::send($data)->toPrivate(true, false, $this->user);
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

            $param = isset($this->response['event']) && $this->response['event'] == 'connection.update';
            if ($param && $this->response['data']['state'] == "open") {

                $scannedNumber = explode('@', $this->response['data']['wuid'])[0];
                $session = WhatsappOtpSession::where('session', $this->session)->update([
                    'status' => 1,
                    'number' => $scannedNumber
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

                NotificationSender::send($data)->toPrivate(true, false, $this->user);
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

            $param = isset($this->response['event']) && $this->response['event'] == 'connection.update';
            if ($param && $this->response['data']['state'] == "close") {

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

                NotificationSender::send($data)->toPrivate(true, false, $this->user);
                return true;

            }

        } catch (\Throwable $th) {

            Log::channel('whatsapp')->error('Error function connectionClose', [
                'message' => $th
            ]);

            return false;
        }
    }

    private function connectionLoss()
    {
        try {

            if (isset($this->response['event']) && ($this->response['event'] == 'logout.instance' || $this->response['event'] == 'remove.instance')) {

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

                NotificationSender::send($data)->toPrivate(true, false, $this->user);
                return true;
            }

        } catch (\Throwable $th) {

            Log::channel('whatsapp')->error('Error function connectionLoss', [
                'message' => $th->getMessage()
            ]);

            return false;
        }
    }
}

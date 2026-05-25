<?php

namespace Modules\Verification\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;
use App\Helpers\Whatsapp\WhatsappHelper;

class VerificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            return view('verification::index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return to_route('dashboard.index');
        }
    }

    public function createSession(Request $request)
    {
        try {

            $session = WhatsappHelper::createSession();
            if (! is_array($session) || empty($session['qrcode']['base64'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => $session['message'] ?? 'Gagal membuat session whatsapp atau QR code tidak tersedia',
                    'meta' => $session['meta'] ?? null,
                ], 500);
            }

            return response()->json([
				'status'  => 'success',
				'data' => $session
			], 200);

		} catch (\Throwable $th) {

			return response()->json([
				'status'  => 'error',
				'message' => "Terjadi kesalahan ".$th->getMessage()
			], 500);
		}
    }

    public function subscribeSession(Request $request)
    {
        $user = Auth::user();
        return response()->json($this->configChannel($user));
    }

    private function configChannel($user)
    {
        $channel = [
            [
                'channel' => 'notification',
                'event'   => 'App\\Events\\PublicNotificationEvent'
            ],
            [
                'channel' => 'private-notification.'.$user->id,
                'event'   => 'App\\Events\\PrivateNotificationEvent'
            ]
        ];

        $waOtpSession = WhatsappOtpSession::where('created_by', $user->id)->get();

        foreach ($waOtpSession as $otpSession) {
            $channel[] = [
                'channel' => 'private-whatsapp-session.'.$otpSession->session,
                'event' => 'App\\Events\\PrivateWhatsappSessionEvent',
            ];
        }

        return $channel;
    }

    public function otpSenderDatatable(Request $request)
    {
        $datatables = WhatsappOtpSession::when(request()->search, function ($query) {
                $query->where(function($query) {
                    $query->where('number', 'ilike', '%'.request()->search.'%');
                });
            })
            ->when(request()->order[0], function ($query) {
                $orderMappings = [
                    "1" => 'number',
                ];

                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            });

        return datatables()->of($datatables)

            ->addColumn('number', function ($otpsender) {
                return view('verification::table_partials._number', [
                    'otpsender' => $otpsender
                ]);
            })
            ->addColumn('session', function ($otpsender) {
                return view('verification::table_partials._session', [
                    'otpsender' => $otpsender
                ]);
            })
            ->addColumn('status', function ($otpsender) {
                return view('verification::table_partials._status', [
                    'otpsender' => $otpsender
                ]);
            })
            ->addColumn('action', function ($otpsender) {
                return view('verification::table_partials._action', [
                    'otpsender' => $otpsender,
                ]);
            })
            ->make();
    }

    public function destroy($id)
    {
        try {
            $session = WhatsappOtpSession::find($id);

            if (! $session) {
                notify()->warning('Data session tidak ditemukan atau sudah terhapus.');

                return to_route('verification.index');
            }

            $disconnect = WhatsappHelper::disconnectSession((string) $session->session);
            if (($disconnect['error'] ?? false) === true) {
                logger()->warning('Verification destroy: WAHA disconnect failed but DB delete continues', [
                    'session_id' => $session->id,
                    'session_code' => $session->session,
                    'disconnect' => $disconnect,
                ]);
            }

            $session->delete();

            notify()->success('Session whatsapp berhasil dihapus.');

            return to_route('verification.index');
        } catch (\Throwable $th) {
            notify()->error('Terjadi kesalahan. '.$th->getMessage());

            return to_route('verification.index');
        }
    }
}

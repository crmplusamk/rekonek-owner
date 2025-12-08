<?php

namespace Modules\OtpHistory\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Verification\App\Models\RegistrationToken;

class OtpHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('otphistory::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function datatable()
    {

        $datatables = RegistrationToken::when(request()->search, function ($query) {
            $query->where(function($query) {
                $query->where('token', 'ilike', '%'.request()->search.'%')
                    ->orWhere('sender', 'ilike', '%'.request()->search.'%')
                    ->orWhere('receiver', 'ilike', '%'.request()->search.'%');
            });
        })
        ->when(request()->order[0], function ($query) {
            $orderMappings = [
                "1" => 'created_at',
            ];

            $column = request()->order[0]['column'];
            $dir    = request()->order[0]['dir'];

            if (isset($orderMappings[$column])) {
                $query->orderBy($orderMappings[$column], $dir)
                    ->orderBy('id', 'desc');
            }
        });

        return datatables()->of($datatables)

            ->addColumn('sender', function ($otp) {
                return view('otphistory::table_partials._sender', [
                    'otp' => $otp
                ]);
            })
            ->addColumn('receiver', function ($otp) {
                return view('otphistory::table_partials._receiver', [
                    'otp' => $otp
                ]);
            })
            ->addColumn('token', function ($otp) {
                return view('otphistory::table_partials._token', [
                    'otp' => $otp
                ]);
            })
            ->addColumn('status', function ($otp) {
                return view('otphistory::table_partials._status', [
                    'otp' => $otp
                ]);
            })
            ->addColumn('action', function ($otp) {
                return view('otphistory::table_partials._action', [
                    'otp' => $otp,
                ]);
            })
            ->make();
    }
}

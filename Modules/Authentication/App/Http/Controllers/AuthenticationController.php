<?php

namespace Modules\Authentication\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('authentication::index');
    }

    public function login(Request $request)
    {
        try {

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $request->session()->regenerate();
                return to_route('dashboard.index');
            }

            return to_route('auth.login')->withErrors('Identitas tersebut tidak cocok dengan data kami.');

        } catch (\Throwable $th) {

            dd($th);
            return back()->withErrors('Internal Server Error');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('auth.login');
    }
}

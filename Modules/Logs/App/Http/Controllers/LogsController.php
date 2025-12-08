<?php

namespace Modules\Logs\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('logs::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('logs::create');
    }
}

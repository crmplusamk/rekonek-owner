<?php

namespace Modules\DeveloperAccess\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\DeveloperAccess\App\Models\DeveloperAccess;

class DeveloperAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $now = now();
        $access = DeveloperAccess::get();
        $count_active = DeveloperAccess::where("end_date", '>', $now)->count();
        $count_inactive = DeveloperAccess::where("end_date", '<', $now)->count();

        return view('developeraccess::index', compact("access", "count_active", "count_inactive"));
    }
}

<?php

namespace Modules\Deleted\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Deleted\App\Models\DeletedCompany;

class DeletedCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $now = now();
        $deleted = DeletedCompany::get();
        $count_pending = DeletedCompany::where("is_status", 0)->count();
        $count_approve = DeletedCompany::where("is_status", 1)->count();
        $count_disapprove = DeletedCompany::where("is_status", 2)->count();

        return view('deleted::company.index', compact("deleted", "count_pending", "count_approve", "count_disapprove"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('deleted::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('deleted::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('deleted::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}

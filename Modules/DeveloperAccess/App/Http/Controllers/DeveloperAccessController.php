<?php

namespace Modules\DeveloperAccess\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\DeveloperAccess\App\Models\DeveloperAccess;
use Modules\DeveloperAccess\App\Repositories\DeveloperAccessRepository;

class DeveloperAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(DeveloperAccessRepository $developerAccessRepository)
    {
        $now = now();
        $access = DeveloperAccess::get();
        $count_active = DeveloperAccess::where("end_date", '>', $now)->count();
        $count_inactive = DeveloperAccess::where("end_date", '<', $now)->count();

        try {
            $crmUsers = $developerAccessRepository->getClientUsersForSelect();
        } catch (\Throwable $e) {
            $crmUsers = new Collection();
        }

        return view('developeraccess::index', compact(
            'access',
            'count_active',
            'count_inactive',
            'crmUsers'
        ));
    }

    /**
     * Simpan token akses developer (DB default backoffice).
     */
    public function store(Request $request, DeveloperAccessRepository $developerAccessRepository)
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'time_access' => 'required|in:1_day,3_days,7_days,14_days,30_days,90_days,forever',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            $developerAccessRepository->create($request->only(['user_id', 'time_access', 'note']));
            notify()->success('Berhasil membuat akses developer');
        } catch (\Throwable $e) {
            notify()->error('Gagal menyimpan: ' . $e->getMessage());
        }

        return redirect()->route('developer-access.index');
    }
}

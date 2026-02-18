<?php

namespace Modules\SettingAffiliator\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Privilege\App\Models\Role;
use Modules\SettingAffiliator\App\Models\AffiliatorConfig;
use Modules\User\App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class SettingAffiliatorController extends Controller
{
    protected function getAffiliatorRole(): ?Role
    {
        return Role::whereRaw("trim(name) = ?", ['affiliator'])->first()
            ?? Role::where('name', 'affiliator')->first();
    }

    public function index()
    {
        return view('settingaffiliator::index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        $role = $this->getAffiliatorRole();
        if (! $role) {
            notify()->error('Role Affiliator tidak ditemukan. Silakan jalankan seeder role.');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $password = Str::random(32);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'is_active' => false,
            ]);
            $user->assignRole($role);
            DB::commit();
            notify()->success('Berhasil menambah data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            notify()->success('Berhasil mengubah data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        try {
            $user->delete();
            notify()->success('Berhasil menghapus data affiliator.');

            return redirect()->route('setting-affiliator.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Reporting usage promo code per affiliator (UI dummy).
     */
    public function usage($id)
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->with(['promoCodesAsAffiliator' => fn ($q) => $q->select('id', 'code', 'affiliator_user_id')])
            ->findOrFail($id);

        $config = AffiliatorConfig::where('user_id', $user->id)->first();

        // Dummy data for UI only
        $firstCode = $user->promoCodesAsAffiliator->first();
        $codeStr = $firstCode ? $firstCode->code : 'PROMO1';
        $pctReg = $config ? (float) $config->commission_value_registrasi : 10;
        $pctPer = $config ? (float) $config->commission_value_perpanjangan : 5;

        $dummyUsageRegistrasi = [
            ['date' => '2025-02-14 10:30', 'code' => $codeStr, 'customer' => 'PT Contoh Registrasi A', 'amount' => 2500000, 'commission_pct' => $pctReg, 'commission_amount' => 250000],
            ['date' => '2025-02-13 14:00', 'code' => $codeStr, 'customer' => 'CV Sample Baru', 'amount' => 1500000, 'commission_pct' => $pctReg, 'commission_amount' => 150000],
            ['date' => '2025-02-12 09:15', 'code' => $codeStr, 'customer' => 'Company XYZ', 'amount' => 3200000, 'commission_pct' => $pctReg, 'commission_amount' => 320000],
        ];
        $dummyUsagePerpanjangan = [
            ['date' => '2025-02-14 11:00', 'code' => $codeStr, 'customer' => 'PT Pelanggan Lama', 'amount' => 1200000, 'commission_pct' => $pctPer, 'commission_amount' => 60000],
            ['date' => '2025-02-13 16:30', 'code' => $codeStr, 'customer' => 'CV Renewal Inc', 'amount' => 2400000, 'commission_pct' => $pctPer, 'commission_amount' => 120000],
        ];

        return view('settingaffiliator::usage', [
            'user' => $user,
            'config' => $config,
            'usageRegistrasi' => $dummyUsageRegistrasi,
            'usagePerpanjangan' => $dummyUsagePerpanjangan,
        ]);
    }

    public function getConfig($id): JsonResponse
    {
        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        $config = AffiliatorConfig::where('user_id', $user->id)->first();

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'commission_type_registrasi' => optional($config)->commission_type_registrasi ?? 'percentage',
            'commission_value_registrasi' => optional($config)->commission_value_registrasi,
            'commission_type_perpanjangan' => optional($config)->commission_type_perpanjangan ?? 'percentage',
            'commission_value_perpanjangan' => optional($config)->commission_value_perpanjangan,
        ]);
    }

    public function saveConfig(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'commission_type_registrasi' => 'required|in:percentage',
            'commission_value_registrasi' => 'required|numeric|min:0|max:100',
            'commission_type_perpanjangan' => 'required|in:percentage',
            'commission_value_perpanjangan' => 'required|numeric|min:0|max:100',
        ]);

        $user = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->findOrFail($id);

        AffiliatorConfig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'commission_type_registrasi' => $request->commission_type_registrasi,
                'commission_value_registrasi' => $request->commission_value_registrasi,
                'commission_type_perpanjangan' => $request->commission_type_perpanjangan,
                'commission_value_perpanjangan' => $request->commission_value_perpanjangan,
            ]
        );

        notify()->success('Berhasil menyimpan konfigurasi komisi affiliator.');

        return redirect()->route('setting-affiliator.index');
    }

    public function datatable(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->whereRaw("trim(name) = ?", ['affiliator'])->orWhere('name', 'affiliator');
        })->with(['promoCodesAsAffiliator' => fn ($q) => $q->select('id', 'code', 'affiliator_user_id')]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('checkbox', fn ($user) => view('settingaffiliator::table_partials._checkbox', ['user' => $user]))
            ->addColumn('name', fn ($user) => view('settingaffiliator::table_partials._name', ['user' => $user]))
            ->addColumn('email', fn ($user) => view('settingaffiliator::table_partials._email', ['user' => $user]))
            ->addColumn('promo_code', fn ($user) => view('settingaffiliator::table_partials._promo_code', ['user' => $user]))
            ->addColumn('action', fn ($user) => view('settingaffiliator::table_partials._action', ['user' => $user]))
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }
}

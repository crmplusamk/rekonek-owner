<?php

namespace Modules\PromoCode\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\PromoCode\App\Models\PromoCode;
use Modules\User\App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class PromoCodeController extends Controller
{
    public function index()
    {
        $activeCount = PromoCode::where('is_active', true)->count();
        $inactiveCount = PromoCode::where('is_active', false)->count();

        return view('promocode::index', compact('activeCount', 'inactiveCount'));
    }

    protected function getAffiliatorUsers()
    {
        return User::whereHas('roles', function ($q) {
            $q->whereRaw('trim(name) = ?', ['affiliator'])->orWhere('name', 'affiliator');
        })->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function create()
    {
        $affiliatorUsers = $this->getAffiliatorUsers();

        return view('promocode::create', compact('affiliatorUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('promo_codes', 'code')->whereNull('deleted_at'),
            ],
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:affiliator,non_affiliator',
            'affiliator_user_id' => 'required_if:type,affiliator|nullable|exists:users,id',
            'discount_type_registrasi' => 'required|in:percentage,nominal',
            'discount_percentage_registrasi' => 'required_if:discount_type_registrasi,percentage|nullable|integer|min:0|max:100',
            'discount_amount_registrasi' => 'required_if:discount_type_registrasi,nominal|nullable|numeric|min:0',
            'min_purchase_registrasi' => 'nullable|numeric|min:0',
            'max_discount_registrasi' => 'nullable|numeric|min:0',
            'discount_type_perpanjangan' => 'required|in:percentage,nominal',
            'discount_percentage_perpanjangan' => 'required_if:discount_type_perpanjangan,percentage|nullable|integer|min:0|max:100',
            'discount_amount_perpanjangan' => 'required_if:discount_type_perpanjangan,nominal|nullable|numeric|min:0',
            'min_purchase_perpanjangan' => 'nullable|numeric|min:0',
            'max_discount_perpanjangan' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $promoCode = PromoCode::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'type' => $request->type,
                'affiliator_user_id' => $request->type === 'affiliator' ? $request->affiliator_user_id : null,
                'discount_type_registrasi' => $request->discount_type_registrasi,
                'discount_percentage_registrasi' => $request->discount_type_registrasi === 'percentage' ? $request->discount_percentage_registrasi : null,
                'discount_amount_registrasi' => $request->discount_type_registrasi === 'nominal' ? $request->discount_amount_registrasi : null,
                'min_purchase_registrasi' => $request->min_purchase_registrasi,
                'max_discount_registrasi' => $request->max_discount_registrasi,
                'discount_type_perpanjangan' => $request->discount_type_perpanjangan,
                'discount_percentage_perpanjangan' => $request->discount_type_perpanjangan === 'percentage' ? $request->discount_percentage_perpanjangan : null,
                'discount_amount_perpanjangan' => $request->discount_type_perpanjangan === 'nominal' ? $request->discount_amount_perpanjangan : null,
                'min_purchase_perpanjangan' => $request->min_purchase_perpanjangan,
                'max_discount_perpanjangan' => $request->max_discount_perpanjangan,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit ?? 1,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active') ? true : false,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Berhasil membuat promo code');

            return redirect()->route('promo-code.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $promoCode = PromoCode::findOrFail($id);
            $affiliatorUsers = $this->getAffiliatorUsers();

            return view('promocode::edit', compact('promoCode', 'affiliatorUsers'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());
            return back();
        }
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promo_codes', 'code')->ignore($id)->whereNull('deleted_at')],
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:affiliator,non_affiliator',
            'affiliator_user_id' => 'required_if:type,affiliator|nullable|exists:users,id',
            'discount_type_registrasi' => 'required|in:percentage,nominal',
            'discount_percentage_registrasi' => 'required_if:discount_type_registrasi,percentage|nullable|integer|min:0|max:100',
            'discount_amount_registrasi' => 'required_if:discount_type_registrasi,nominal|nullable|numeric|min:0',
            'min_purchase_registrasi' => 'nullable|numeric|min:0',
            'max_discount_registrasi' => 'nullable|numeric|min:0',
            'discount_type_perpanjangan' => 'required|in:percentage,nominal',
            'discount_percentage_perpanjangan' => 'required_if:discount_type_perpanjangan,percentage|nullable|integer|min:0|max:100',
            'discount_amount_perpanjangan' => 'required_if:discount_type_perpanjangan,nominal|nullable|numeric|min:0',
            'min_purchase_perpanjangan' => 'nullable|numeric|min:0',
            'max_discount_perpanjangan' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $promoCode->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'type' => $request->type,
                'affiliator_user_id' => $request->type === 'affiliator' ? $request->affiliator_user_id : null,
                'discount_type_registrasi' => $request->discount_type_registrasi,
                'discount_percentage_registrasi' => $request->discount_type_registrasi === 'percentage' ? $request->discount_percentage_registrasi : null,
                'discount_amount_registrasi' => $request->discount_type_registrasi === 'nominal' ? $request->discount_amount_registrasi : null,
                'min_purchase_registrasi' => $request->min_purchase_registrasi,
                'max_discount_registrasi' => $request->max_discount_registrasi,
                'discount_type_perpanjangan' => $request->discount_type_perpanjangan,
                'discount_percentage_perpanjangan' => $request->discount_type_perpanjangan === 'percentage' ? $request->discount_percentage_perpanjangan : null,
                'discount_amount_perpanjangan' => $request->discount_type_perpanjangan === 'nominal' ? $request->discount_amount_perpanjangan : null,
                'min_purchase_perpanjangan' => $request->min_purchase_perpanjangan,
                'max_discount_perpanjangan' => $request->max_discount_perpanjangan,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit ?? 1,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active') ? true : false,
                'description' => $request->description,
            ]);

            notify()->success('Berhasil mengubah promo code');
            return redirect()->route('promo-code.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $promoCode = PromoCode::findOrFail($id);
            if ($promoCode->usages()->count() > 0) {
                notify()->warning('Promo code tidak dapat dihapus karena sudah digunakan');
                return back();
            }
            $promoCode->delete();
            notify()->success('Berhasil menghapus promo code');
            return redirect()->route('promo-code.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {
            $promoCode = PromoCode::findOrFail($id);
            $promoCode->update(['is_active' => ! $promoCode->is_active]);
            notify()->success('Berhasil mengubah status promo code');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());
            return back();
        }
    }

    public function datatable(Request $request)
    {
        $query = PromoCode::query();
        if ($request->has('filter_status')) {
            $query->where('is_active', $request->filter_status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('checkbox', fn ($promoCode) => view('promocode::table_partials._checkbox', ['promoCode' => $promoCode]))
            ->addColumn('code', fn ($promoCode) => view('promocode::table_partials._code', ['promoCode' => $promoCode]))
            ->addColumn('name', fn ($promoCode) => view('promocode::table_partials._name', ['promoCode' => $promoCode]))
            ->addColumn('type', fn ($promoCode) => view('promocode::table_partials._type', ['promoCode' => $promoCode]))
            ->addColumn('quota_info', fn ($promoCode) => view('promocode::table_partials._quota_info', ['promoCode' => $promoCode]))
            ->addColumn('status', fn ($promoCode) => view('promocode::table_partials._status', ['promoCode' => $promoCode]))
            ->addColumn('created_at', fn ($promoCode) => view('promocode::table_partials._created_at', ['promoCode' => $promoCode]))
            ->addColumn('action', fn ($promoCode) => view('promocode::table_partials._action', ['promoCode' => $promoCode]))
            ->rawColumns(['checkbox', 'status', 'action'])
            ->make(true);
    }
}

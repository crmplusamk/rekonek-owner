<?php

namespace Modules\Referral\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Referral\App\Models\Referral;
use Modules\Referral\App\Models\ReferralUsage;
use Yajra\DataTables\Facades\DataTables;

class ReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activeCount = Referral::where('is_active', true)->count();
        $inactiveCount = Referral::where('is_active', false)->count();
        
        return view('referral::index', compact('activeCount', 'inactiveCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('referral::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('referrals', 'code')->whereNull('deleted_at'),
            ],
            'name' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,nominal',
            'discount_percentage' => 'required_if:discount_type,percentage|nullable|integer|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,nominal|nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            Referral::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'discount_type' => $request->discount_type,
                'discount_percentage' => $request->discount_type === 'percentage' ? $request->discount_percentage : null,
                'discount_amount' => $request->discount_type === 'nominal' ? $request->discount_amount : null,
                'min_purchase' => $request->min_purchase,
                'max_discount' => $request->max_discount,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit ?? 1,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active') ? true : false,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Berhasil membuat referral code');
            return redirect()->route('referral.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {
            $referral = Referral::with('usages')->findOrFail($id);
            
            // Statistics
            $totalUsage = $referral->usages()->count();
            $totalDiscount = $referral->usages()->sum('discount_amount');
            $totalPurchase = $referral->usages()->sum('purchase_amount');
            $remainingQuota = $referral->usage_limit ? ($referral->usage_limit - $referral->used_count) : 'Unlimited';
            $usagePercentage = $referral->usage_limit ? round(($referral->used_count / $referral->usage_limit) * 100, 2) : 0;
            
            // Recent usages
            $recentUsages = $referral->usages()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('referral::show', compact('referral', 'totalUsage', 'totalDiscount', 'totalPurchase', 'remainingQuota', 'usagePercentage', 'recentUsages'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $referral = Referral::findOrFail($id);
            return view('referral::edit', compact('referral'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $referral = Referral::findOrFail($id);

        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('referrals', 'code')->ignore($id)->whereNull('deleted_at'),
            ],
            'name' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,nominal',
            'discount_percentage' => 'required_if:discount_type,percentage|nullable|integer|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,nominal|nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $referral->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'discount_type' => $request->discount_type,
                'discount_percentage' => $request->discount_type === 'percentage' ? $request->discount_percentage : null,
                'discount_amount' => $request->discount_type === 'nominal' ? $request->discount_amount : null,
                'min_purchase' => $request->min_purchase,
                'max_discount' => $request->max_discount,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit ?? 1,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active') ? true : false,
                'description' => $request->description,
            ]);

            notify()->success('Berhasil mengubah referral code');
            return redirect()->route('referral.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $referral = Referral::findOrFail($id);
            
            if ($referral->usages()->count() > 0) {
                notify()->warning('Referral code tidak dapat dihapus karena sudah digunakan');
                return back();
            }

            $referral->delete();
            notify()->success('Berhasil menghapus referral code');
            return redirect()->route('referral.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Toggle status
     */
    public function status($id)
    {
        try {
            $referral = Referral::findOrFail($id);
            $referral->update([
                'is_active' => !$referral->is_active
            ]);

            notify()->success('Berhasil mengubah status referral code');
            return back();
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Datatable
     */
    public function datatable(Request $request)
    {
        $query = Referral::query();

        if ($request->has('filter_status')) {
            $query->where('is_active', $request->filter_status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function($referral) {
                return view('referral::table_partials._checkbox', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('code', function($referral) {
                return view('referral::table_partials._code', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('name', function($referral) {
                return view('referral::table_partials._name', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('discount_info', function($referral) {
                return view('referral::table_partials._discount_info', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('quota_info', function($referral) {
                return view('referral::table_partials._quota_info', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('status', function($referral) {
                return view('referral::table_partials._status', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('created_at', function($referral) {
                return view('referral::table_partials._created_at', [
                    'referral' => $referral
                ]);
            })
            ->addColumn('action', function($referral) {
                return view('referral::table_partials._action', [
                    'referral' => $referral,
                ]);
            })
            ->rawColumns(['checkbox', 'status', 'action'])
            ->make(true);
    }
}

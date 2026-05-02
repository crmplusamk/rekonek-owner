<?php

namespace Modules\Customer\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Customer\App\Models\Customer;

class CustomerRepository
{
    private const SUBSCRIPTION_CONTEXT_ACTIVE = 'active';
    private const SUBSCRIPTION_CONTEXT_GRACE = 'grace';
    private const SUBSCRIPTION_CONTEXT_ENDED = 'ended';
    private const SUBSCRIPTION_CONTEXT_INACTIVE = 'inactive';
    private const SUBSCRIPTION_CONTEXT_NONE = 'none';

    public function list($request)
    {
        $data = Customer::when($request['search'] ?? null, function ($query) use ($request) {
            $query->where(function($query) use($request) {
                $query->where('name', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('code', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('phone', 'ilike', '%'.$request['search'].'%')
                    ->orWhere('email', 'ilike', '%'.$request['search'].'%');
            });
        })
        ->where('is_active', true)
        ->paginate(10);

        return $data;
    }

    public function create($request)
    {
        $data = Customer::create([
            'name' => $request['name'],
            'code' => Str::upper(Str::random(8)),
            'email' => $request['email'],
            'phone' => $request['phone'],
            'company_id' => Str::uuid(),
            'is_active' => true,
            'is_customer' => true,
        ]);

        return $data;
    }

    public function getById($id)
    {
        $data = Customer::findOrFail($id);
        return $data;
    }

    public function getByCompanyId($id)
    {
        $data = Customer::where("company_id", $id)->first();
        return $data;
    }

    public function detail($id)
    {
        $data = Customer::findOrFail($id);
        return $data;
    }

    public function update($request, $id)
    {
        $data = $this->getById($id);
        $data->update([
            'name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
        ]);

        return $data;
    }

    public function delete($id)
    {
        $data = $this->getById($id);
        $data->delete();

        return 204;
    }

    public function status($id)
    {
        $data = $this->getById($id);
        $data->update([
            'is_active' => !$data->is_active
        ]);

        return $data;
    }

    public function datatable()
    {
        $datatables = Customer::when(request()->search, function ($query) {
                $query->where(function($query) {
                    $query->where('contacts.name', 'ilike', '%'.request()->search.'%')
                        ->orWhere('contacts.code', 'ilike', '%'.request()->search.'%')
                        ->orWhere('contacts.phone', 'ilike', '%'.request()->search.'%')
                        ->orWhere('contacts.email', 'ilike', '%'.request()->search.'%');
                });
            })
            ->when(request()->filled('filter_status'), function ($query) {
                $query->where('contacts.is_active', request()->filter_status === '1');
            })
            ->when(request()->order[0], function ($query) {
                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

                $orderMappings = [
                    "1" => 'contacts.name',
                    "2" => 'contacts.code',
                    "3" => 'contacts.phone',
                    "4" => 'contacts.created_at',
                    "9" => 'contacts.is_active',
                ];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('contacts.id', 'desc');
                } elseif ($column === '5') {
                    $this->orderByLastLogin($query, $dir);
                } elseif (in_array($column, ['6', '7', '8'])) {
                    $this->orderBySubscription($query, $column, $dir);
                }
            })
        ->orderBy('contacts.created_at', 'desc');

        return datatables()->of($datatables)

            ->addColumn('checkbox', function ($customer) {
                return view('customer::table_partials._checkbox', [
                    'customer' => $customer
                ]);
            })
            ->editColumn('created_at', function ($customer) {
                return $customer->created_at->format('d M Y H:i');
            })
            ->addColumn('phone', function ($customer) {
                return view('customer::table_partials._phone', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('last_login', function ($customer) {
                $lastLogin = DB::connection('client')
                    ->table('users')
                    ->where('company_id', $customer->company_id)
                    ->whereNotNull('last_login_at')
                    ->max('last_login_at');

                if (!$lastLogin) {
                    return '-';
                }

                return \Carbon\Carbon::parse($lastLogin)->format('d M Y H:i');
            })
            ->addColumn('code', function ($customer) {
                return view('customer::table_partials._code', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('email', function ($customer) {
                return view('customer::table_partials._email', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('status', function ($customer) {
                return view('customer::table_partials._status', [
                    'customer' => $customer
                ]);
            })
            ->addColumn('subscription', function ($customer) {
                $resolved = $this->resolveSubscriptionForCompany($customer->company_id);

                return view('customer::table_partials._subscription', [
                    'customer' => $customer,
                    'subscription' => $resolved['subscription'],
                    'package' => $resolved['package'],
                    'context' => $resolved['context'],
                    'contextLabel' => $resolved['context_label'],
                ]);
            })
            ->addColumn('subscription_started_at', function ($customer) {
                $resolved = $this->resolveSubscriptionForCompany($customer->company_id);

                if (! $resolved['subscription'] || ! $resolved['subscription']->started_at) {
                    return '<span class="text-muted">-</span>';
                }

                return Carbon::parse($resolved['subscription']->started_at)->format('d M Y');
            })
            ->addColumn('subscription_expired_at', function ($customer) {
                $resolved = $this->resolveSubscriptionForCompany($customer->company_id);

                if (! $resolved['subscription'] || ! $resolved['subscription']->expired_at) {
                    return '<span class="text-muted">-</span>';
                }

                return Carbon::parse($resolved['subscription']->expired_at)->format('d M Y');
            })
            ->addColumn('action', function ($customer) {
                return view('customer::table_partials._action', [
                    'customer' => $customer,
                ]);
            })
            ->rawColumns(['checkbox', 'phone', 'code', 'email', 'status', 'subscription', 'subscription_started_at', 'subscription_expired_at', 'action'])
            ->make();
    }

    private function orderByLastLogin($query, string $dir): void
    {
        $companyIds = DB::connection('client')
            ->table('users')
            ->whereNotNull('company_id')
            ->whereNotNull('last_login_at')
            ->select('company_id')
            ->groupBy('company_id')
            ->orderByRaw('MAX(last_login_at) '.$dir)
            ->pluck('company_id')
            ->values()
            ->all();

        if (empty($companyIds)) {
            return;
        }

        $quotedIds = collect($companyIds)
            ->map(fn ($companyId) => "'".str_replace("'", "''", $companyId)."'")
            ->implode(',');

        $query->orderByRaw("array_position(ARRAY[$quotedIds]::uuid[], contacts.company_id) ASC NULLS LAST")
            ->orderBy('contacts.id', 'desc');
    }

    private function orderBySubscription($query, string $column, string $dir): void
    {
        $sortColumn = match ($column) {
            '6' => 'p.name',
            '7' => 'sp.started_at',
            default => 'sp.expired_at',
        };

        $query->leftJoin('subscription_packages as sp', function ($join) {
                $join->on('sp.company_id', '=', 'contacts.company_id')
                    ->whereRaw('sp.id = (
                        SELECT sp2.id
                        FROM subscription_packages sp2
                        WHERE sp2.company_id = contacts.company_id
                        ORDER BY sp2.expired_at DESC, sp2.started_at DESC, sp2.created_at DESC
                        LIMIT 1
                    )');
            })
            ->leftJoin('packages as p', 'p.id', '=', 'sp.package_id')
            ->select('contacts.*')
            ->orderByRaw($sortColumn.' '.$dir.' NULLS LAST')
            ->orderBy('contacts.id', 'desc');
    }

    /**
     * Langganan untuk tabel customer: utamakan yang masih efektif hari ini;
     * jika tidak ada, fallback ke baris terbaru per company (grace / berakhir) agar kolom tidak kosong.
     *
     * @return array{subscription: object|null, package: object|null, context: string, context_label: string|null}
     */
    private function resolveSubscriptionForCompany(?string $companyId): array
    {
        if (! $companyId) {
            return [
                'subscription' => null,
                'package' => null,
                'context' => self::SUBSCRIPTION_CONTEXT_NONE,
                'context_label' => null,
            ];
        }

        $today = now()->toDateString();
        $subscription = DB::table('subscription_packages')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_grace', self::SUBSCRIPTION_CONTEXT_ACTIVE)
            ->whereDate('started_at', '<=', $today)
            ->whereDate('expired_at', '>=', $today)
            ->orderByDesc('expired_at')
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->first();
        $context = self::SUBSCRIPTION_CONTEXT_ACTIVE;

        if (! $subscription) {
            $subscription = DB::table('subscription_packages')
                ->where('company_id', $companyId)
                ->orderByDesc('expired_at')
                ->orderByDesc('started_at')
                ->orderByDesc('created_at')
                ->first();

            if (! $subscription) {
                return [
                    'subscription' => null,
                    'package' => null,
                    'context' => self::SUBSCRIPTION_CONTEXT_NONE,
                    'context_label' => null,
                ];
            }

            if ($subscription->is_grace === self::SUBSCRIPTION_CONTEXT_GRACE) {
                $context = self::SUBSCRIPTION_CONTEXT_GRACE;
            } elseif ($subscription->expired_at && Carbon::parse($subscription->expired_at)->toDateString() < $today) {
                $context = self::SUBSCRIPTION_CONTEXT_ENDED;
            } else {
                $context = self::SUBSCRIPTION_CONTEXT_INACTIVE;
            }
        }

        $package = null;
        if ($subscription->package_id) {
            $package = DB::table('packages')
                ->where('id', $subscription->package_id)
                ->first();
        }

        return [
            'subscription' => $subscription,
            'package' => $package,
            'context' => $context,
            'context_label' => $this->subscriptionContextLabel($context),
        ];
    }

    private function subscriptionContextLabel(string $context): ?string
    {
        return match ($context) {
            self::SUBSCRIPTION_CONTEXT_GRACE => 'Grace',
            self::SUBSCRIPTION_CONTEXT_ENDED => 'Tidak aktif',
            self::SUBSCRIPTION_CONTEXT_INACTIVE => 'Non-aktif',
            default => null,
        };
    }

}

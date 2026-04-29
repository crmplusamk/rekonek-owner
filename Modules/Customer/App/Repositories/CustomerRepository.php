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
                    $query->where('name', 'ilike', '%'.request()->search.'%')
                        ->orWhere('code', 'ilike', '%'.request()->search.'%')
                        ->orWhere('phone', 'ilike', '%'.request()->search.'%')
                        ->orWhere('email', 'ilike', '%'.request()->search.'%');
                });
            })
            ->when(request()->order[0], function ($query) {
                $column = request()->order[0]['column'];
                $dir    = request()->order[0]['dir'];

                $orderMappings = [
                    "1" => 'name',
                    "2" => 'code',
                    "5" => 'created_at',
                ];

                if (isset($orderMappings[$column])) {
                    $query->orderBy($orderMappings[$column], $dir)
                        ->orderBy('id', 'desc');
                }
            })
        ->orderBy('created_at', 'desc');

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
            ->addColumn('subscription_period', function ($customer) {
                $resolved = $this->resolveSubscriptionForCompany($customer->company_id);

                return view('customer::table_partials._subscription_period', [
                    'subscription' => $resolved['subscription'],
                    'context' => $resolved['context'],
                    'contextLabel' => $resolved['context_label'],
                ]);
            })
            ->addColumn('action', function ($customer) {
                return view('customer::table_partials._action', [
                    'customer' => $customer,
                ]);
            })
            ->make();
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

<?php

namespace Modules\DeveloperAccess\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\DeveloperAccess\App\Models\DeveloperAccess;

class DeveloperAccessRepository
{

    /**
     * All CRM users for backoffice select (connection `client`), with company name when available.
     */
    public function getClientUsersForSelect()
    {
        $users = DB::connection('client')
            ->table('users')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company_id', 'is_superadmin', 'is_active']);

        if ($users->isEmpty()) {
            return $users;
        }

        $companyIds = $users->pluck('company_id')->filter()->unique()->values()->all();
        $companyNames = [];
        if ($companyIds !== []) {
            $companyNames = DB::connection('client')
                ->table('companies')
                ->whereIn('id', $companyIds)
                ->pluck('name', 'id')
                ->all();
        }

        return $users->map(function ($user) use ($companyNames) {
            $cid = $user->company_id ?? null;
            $user->company_name = $cid && isset($companyNames[$cid])
                ? $companyNames[$cid]
                : '';

            return $user;
        })->values();
    }

    public function create($request)
    {
        // Calculate start and end date based on time_access
        $startDate = Carbon::now();
        $endDate = $this->calculateEndDate($request['time_access']);

        // Get user info from client database if user_id is provided
        $userInfo = null;
        if (!empty($request['user_id'])) {
            $userInfo = DB::connection('client')
                ->table('users')
                ->where('id', $request['user_id'])
                ->first();
        }

        $companyId = $request['company_id'] ?? ($userInfo->company_id ?? null);
        $companyName = $request['company_name'] ?? null;
        if ($companyName === null && $companyId) {
            $companyName = DB::connection('client')
                ->table('companies')
                ->where('id', $companyId)
                ->value('name');
        }

        // Generate unique token
        $tokenAccess = Str::uuid()->toString();

        $data = DeveloperAccess::create([
            'account_name' => $userInfo->name ?? $request['account_name'] ?? null,
            'account_email' => $userInfo->email ?? $request['account_email'] ?? null,
            'token_access' => $tokenAccess,
            'time_access' => $request['time_access'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => $request['note'] ?? null,
            'company_id' => $companyId,
            'company_name' => $companyName,
            'user_id' => $request['user_id'] ?? null,
        ]);

        try {
            $this->mirrorDeveloperAccessToClient($data);
        } catch (\Throwable $e) {
            $data->delete();
            throw $e;
        }

        return $data;
    }

    /**
     * Retalk memvalidasi token di DB CRM (koneksi client), bukan di DB backoffice.
     */
    protected function mirrorDeveloperAccessToClient(DeveloperAccess $row): void
    {
        DB::connection('client')->table('setting_developer_access')->updateOrInsert(
            ['id' => $row->id],
            [
                'user_id' => $row->user_id,
                'token_access' => $row->token_access,
                'time_access' => $row->time_access,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'note' => $row->note,
                'company_id' => $row->company_id,
                'is_active' => true,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Calculate end date based on time_access string.
     */
    private function calculateEndDate($timeAccess)
    {
        $now = Carbon::now();

        switch ($timeAccess) {
            case '1_day':
                return $now->copy()->addDays(1);
            case '3_days':
                return $now->copy()->addDays(3);
            case '7_days':
                return $now->copy()->addDays(7);
            case '14_days':
                return $now->copy()->addDays(14);
            case '30_days':
                return $now->copy()->addDays(30);
            case '90_days':
                return $now->copy()->addDays(90);
            case 'forever':
                return $now->copy()->addYears(100); // Far future for "forever"
            default:
                return $now->copy()->addDays(7);
        }
    }

    public function destroyBulkByToken($request)
    {
        $tokens = $request['tokens'] ?? [];
        if ($tokens === [] || $tokens === null) {
            return 0;
        }

        DB::connection('client')->table('setting_developer_access')->whereIn('token_access', $tokens)->delete();

        return DeveloperAccess::whereIn('token_access', $tokens)->delete();
    }
}

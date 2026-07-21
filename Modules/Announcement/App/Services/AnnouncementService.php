<?php

namespace Modules\Announcement\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Announcement\App\Models\Announcement;
use Modules\Announcement\App\Models\AnnouncementTarget;
use Modules\Customer\App\Models\Customer;

class AnnouncementService
{
    public function getById(string $id): Announcement
    {
        return Announcement::with('targets')->findOrFail($id);
    }

    public function detail(string $id): Announcement
    {
        return $this->getById($id);
    }

    public function datatable()
    {
        $query = Announcement::query()
            ->with('targets')
            ->when(request()->search, function ($builder) {
                $search = request()->search;

                $builder->where(function ($inner) use ($search) {
                    $inner->where('title', 'ilike', '%'.$search.'%')
                        ->orWhere('message', 'ilike', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at');

        return datatables()->of($query)
            ->editColumn('title', function (Announcement $announcement) {
                $messagePreview = \Illuminate\Support\Str::limit(strip_tags($announcement->message), 90);

                return '<div class="text-left"><strong>'.$announcement->title.'</strong><small class="d-block text-muted mt-1">'.$messagePreview.'</small></div>';
            })
            ->addColumn('type_badge', fn (Announcement $announcement) => view('announcement::table_partials._type', compact('announcement')))
            ->addColumn('target_summary', fn (Announcement $announcement) => view('announcement::table_partials._target_summary', compact('announcement')))
            ->addColumn('status_badge', fn (Announcement $announcement) => view('announcement::table_partials._status', compact('announcement')))
            ->addColumn('schedule', fn (Announcement $announcement) => view('announcement::table_partials._schedule', compact('announcement')))
            ->addColumn('action', fn (Announcement $announcement) => view('announcement::table_partials._action', compact('announcement')))
            ->rawColumns(['title', 'type_badge', 'target_summary', 'status_badge', 'schedule', 'action'])
            ->make(true);
    }

    public function getOverlappingAnnouncements(array $payload, ?string $ignoreId = null): Collection
    {
        $startAt = Carbon::parse($payload['start_at']);
        $endAt = !empty($payload['end_at']) ? Carbon::parse($payload['end_at']) : null;

        return Announcement::query()
            ->with('targets')
            ->whereIn('status', [Announcement::STATUS_ACTIVE, Announcement::STATUS_SCHEDULED])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($startAt, $endAt) {
                $query->where('start_at', '<=', $endAt ?: Carbon::create(9999, 12, 31, 23, 59, 59))
                    ->where(function ($inner) use ($startAt) {
                        $inner->whereNull('end_at')
                            ->orWhere('end_at', '>=', $startAt);
                    });
            })
            ->get()
            ->map(function (Announcement $announcement) {
                return [
                    'id' => $announcement->id,
                    'status' => $announcement->status,
                    'start_at' => $announcement->start_at,
                    'end_at' => $announcement->end_at,
                    'targets' => $announcement->targets->map(fn (AnnouncementTarget $target) => [
                        'target_type' => $target->target_type,
                        'target_id' => $target->target_id,
                        'target_value' => $target->target_value,
                    ])->all(),
                ];
            });
    }

    public function syncTargets(Announcement $announcement, array $targets): void
    {
        $announcement->targets()->delete();

        foreach ($targets as $target) {
            $announcement->targets()->create($target);
        }
    }

    public function companyOptions(?string $search = null)
    {
        return Customer::query()
            ->select('company_id', 'name', 'email')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('email', 'ilike', '%'.$search.'%')
                        ->orWhere('code', 'ilike', '%'.$search.'%');
                });
            })
            ->whereNotNull('company_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->unique('company_id')
            ->values();
    }

    public function companiesByIds(array $companyIds)
    {
        return Customer::query()
            ->select('company_id', 'name', 'email')
            ->whereIn('company_id', $companyIds)
            ->whereNotNull('company_id')
            ->where('is_customer', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->unique('company_id')
            ->values();
    }
}

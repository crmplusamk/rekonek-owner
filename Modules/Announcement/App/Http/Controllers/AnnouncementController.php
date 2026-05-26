<?php

namespace Modules\Announcement\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Announcement\App\Http\Requests\AnnouncementStoreRequest;
use Modules\Announcement\App\Http\Requests\AnnouncementUpdateRequest;
use Modules\Announcement\App\Models\Announcement;
use Modules\Announcement\App\Repositories\AnnouncementRepository;
use Modules\Announcement\App\Services\AnnouncementPublishService;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementRepository $announcementRepo,
        protected AnnouncementPublishService $publishService,
    ) {
    }

    public function index()
    {
        return view('announcement::index');
    }

    public function create()
    {
        return view('announcement::create');
    }

    public function store(AnnouncementStoreRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $payload = $this->normalizePayload($request);
            $targets = $this->normalizeTargets($request);
            $result = $this->publishService->resolvePublication(
                $payload,
                $targets,
                $this->announcementRepo->getOverlappingAnnouncements($payload)
            );

            $announcement = Announcement::create([
                ...$payload,
                'status' => $result->status,
                'published_at' => in_array($result->status, [Announcement::STATUS_ACTIVE, Announcement::STATUS_SCHEDULED], true) ? now() : null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->announcementRepo->syncTargets($announcement, $result->targets);

            DB::commit();

            notify()->success('Berhasil membuat pengumuman');
            if ($result->conflictReason) {
                notify()->warning($result->conflictReason);
            }

            return redirect()->route('announcement.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function show($id)
    {
        return redirect()->route('announcement.edit', $id);
    }

    public function edit($id)
    {
        try {
            $announcement = $this->announcementRepo->detail($id);
            $companyIds = $announcement->targets
                ->where('target_type', Announcement::TARGET_COMPANY)
                ->pluck('target_value')
                ->filter()
                ->values()
                ->all();

            $selectedCompanies = $announcement->targets
                ->where('target_type', Announcement::TARGET_COMPANY);

            $selectedCompanies = $this->announcementRepo->companiesByIds($companyIds)
                ->map(fn ($company) => [
                    'id' => $company->company_id,
                    'text' => $company->name,
                ])
                ->values();

            return view('announcement::edit', compact('announcement', 'selectedCompanies'));
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back();
        }
    }

    public function update(AnnouncementUpdateRequest $request, $id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $announcement = $this->announcementRepo->getById($id);
            $payload = $this->normalizePayload($request);
            $targets = $this->normalizeTargets($request);
            $result = $this->publishService->resolvePublication(
                $payload,
                $targets,
                $this->announcementRepo->getOverlappingAnnouncements($payload, $announcement->id)
            );

            $announcement->update([
                ...$payload,
                'status' => $result->status,
                'published_at' => in_array($result->status, [Announcement::STATUS_ACTIVE, Announcement::STATUS_SCHEDULED], true) ? now() : null,
                'updated_by' => auth()->id(),
            ]);

            $this->announcementRepo->syncTargets($announcement, $result->targets);

            DB::commit();

            notify()->success('Berhasil mengubah pengumuman');
            if ($result->conflictReason) {
                notify()->warning($result->conflictReason);
            }

            return redirect()->route('announcement.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $announcement = $this->announcementRepo->getById($id);
            $announcement->targets()->delete();
            $announcement->forceDelete();

            notify()->success('Berhasil menghapus pengumuman');

            return redirect()->route('announcement.index');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan: '.$e->getMessage());

            return back();
        }
    }

    public function datatable(Request $request)
    {
        return $this->announcementRepo->datatable();
    }

    public function companies(Request $request)
    {
        return response()->json([
            'data' => $this->announcementRepo->companyOptions($request->get('search')),
        ]);
    }

    protected function normalizePayload(Request $request): array
    {
        $startAt = $request->date('start_at');
        $endAt = $request->filled('end_at') ? $request->date('end_at') : null;

        return [
            'title' => $request->string('title')->toString(),
            'message' => $request->string('message')->toString(),
            'type' => $request->string('type')->toString(),
            'action_label' => $request->input('action_label'),
            'action_url' => $request->input('action_url'),
            'start_at' => $startAt ? $startAt->toDateTimeString() : null,
            'end_at' => $endAt ? $endAt->toDateTimeString() : null,
            'priority' => (int) $request->input('priority', 0),
        ];
    }

    protected function normalizeTargets(Request $request): array
    {
        if ($request->input('target_mode') !== 'company') {
            return [];
        }

        return collect((array) $request->input('company_ids', []))
            ->filter()
            ->map(fn ($companyId) => [
                'target_type' => Announcement::TARGET_COMPANY,
                'target_id' => null,
                'target_value' => (string) $companyId,
            ])
            ->values()
            ->all();
    }
}

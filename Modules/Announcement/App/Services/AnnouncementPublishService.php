<?php

namespace Modules\Announcement\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Announcement\App\Models\Announcement;
use Modules\Announcement\App\Support\AnnouncementConflictResult;

class AnnouncementPublishService
{
    public function resolvePublication(
        array $payload,
        array $targets,
        Collection $existingAnnouncements,
        ?Carbon $now = null
    ): AnnouncementConflictResult {
        $now = $now ?: now();
        $normalizedTargets = $this->normalizeTargets($targets);

        if ($this->hasConflict($payload, $normalizedTargets, $existingAnnouncements)) {
            return new AnnouncementConflictResult(
                Announcement::STATUS_DRAFT,
                $normalizedTargets,
                $this->buildConflictReason($normalizedTargets, $existingAnnouncements, $payload)
            );
        }

        $status = Carbon::parse($payload['start_at'])->gt($now)
            ? Announcement::STATUS_SCHEDULED
            : Announcement::STATUS_ACTIVE;

        return new AnnouncementConflictResult($status, $normalizedTargets);
    }

    public function normalizeTargets(array $targets): array
    {
        return collect($targets)
            ->filter(fn (array $target) => !empty($target['target_type']) && (!empty($target['target_value']) || !empty($target['target_id'])))
            ->map(function (array $target) {
                return [
                    'target_type' => $target['target_type'],
                    'target_id' => $target['target_id'] ?? null,
                    'target_value' => $target['target_value'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function hasConflict(array $payload, array $targets, Collection $existingAnnouncements): bool
    {
        foreach ($existingAnnouncements as $announcement) {
            if (!$this->isPublishRelevantStatus($announcement['status'] ?? null)) {
                continue;
            }

            if (!$this->hasOverlappingWindow($payload, $announcement)) {
                continue;
            }

            if ($this->isScopeConflict($targets, $announcement['targets'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private function buildConflictReason(array $targets, Collection $existingAnnouncements, array $payload): string
    {
        foreach ($existingAnnouncements as $announcement) {
            if (!$this->isPublishRelevantStatus($announcement['status'] ?? null)) {
                continue;
            }

            if (!$this->hasOverlappingWindow($payload, $announcement)) {
                continue;
            }

            if ($this->isGlobalTarget($targets) || $this->isGlobalTarget($announcement['targets'] ?? [])) {
                return 'Masih ada pengumuman global aktif atau terjadwal pada rentang waktu yang sama.';
            }

            if ($this->hasCompanyTargetOverlap($targets, $announcement['targets'] ?? [])) {
                return 'Masih ada pengumuman aktif atau terjadwal untuk company yang sama pada rentang waktu yang sama.';
            }
        }

        return 'Pengumuman bentrok dengan pengumuman aktif atau terjadwal lainnya.';
    }

    private function isScopeConflict(array $targets, array $existingTargets): bool
    {
        if ($this->isGlobalTarget($targets) || $this->isGlobalTarget($existingTargets)) {
            return true;
        }

        return $this->hasCompanyTargetOverlap($targets, $existingTargets);
    }

    private function hasCompanyTargetOverlap(array $targets, array $existingTargets): bool
    {
        $incomingCompanyIds = collect($targets)
            ->where('target_type', Announcement::TARGET_COMPANY)
            ->pluck('target_value')
            ->filter()
            ->all();

        $existingCompanyIds = collect($existingTargets)
            ->where('target_type', Announcement::TARGET_COMPANY)
            ->pluck('target_value')
            ->filter()
            ->all();

        return !empty(array_intersect($incomingCompanyIds, $existingCompanyIds));
    }

    private function isGlobalTarget(array $targets): bool
    {
        return empty($targets);
    }

    private function hasOverlappingWindow(array $payload, array $existingAnnouncement): bool
    {
        $startAt = Carbon::parse($payload['start_at']);
        $endAt = !empty($payload['end_at']) ? Carbon::parse($payload['end_at']) : null;
        $existingStartAt = Carbon::parse($existingAnnouncement['start_at']);
        $existingEndAt = !empty($existingAnnouncement['end_at']) ? Carbon::parse($existingAnnouncement['end_at']) : null;

        $incomingEnd = $endAt ?: Carbon::create(9999, 12, 31, 23, 59, 59);
        $existingEnd = $existingEndAt ?: Carbon::create(9999, 12, 31, 23, 59, 59);

        return $startAt->lte($existingEnd) && $existingStartAt->lte($incomingEnd);
    }

    private function isPublishRelevantStatus(?string $status): bool
    {
        return in_array($status, [
            Announcement::STATUS_ACTIVE,
            Announcement::STATUS_SCHEDULED,
        ], true);
    }
}

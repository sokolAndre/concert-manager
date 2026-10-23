<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    public function between(
        CarbonInterface $from,
        CarbonInterface $to,
        ?array $projectIds = null,
    ): Collection {
        return Event::query()
            ->with(['project', 'venue'])
            ->whereBetween('starts_at', [
                $from->copy()->utc(),
                $to->copy()->utc(),
            ])
            ->when(
                $projectIds !== null,
                fn ($query) => $query->whereIn('project_id', $projectIds),
            )
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->get();
    }

    public function upcoming(
        int $limit,
        int $offset = 0,
        ?array $projectIds = null,
    ): Collection {
        return Event::query()
            ->with(['project', 'venue'])
            ->where('starts_at', '>=', now())
            ->when(
                $projectIds !== null,
                fn ($query) => $query->whereIn('project_id', $projectIds),
            )
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}

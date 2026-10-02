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
    ): Collection {
        return Event::query()
            ->with(['project', 'venue'])
            ->whereBetween('starts_at', [
                $from->copy()->utc(),
                $to->copy()->utc(),
            ])
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->get();
    }

    public function upcoming(int $limit = 10): Collection
    {
        return Event::query()
            ->with(['project', 'venue'])
            ->where('starts_at', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }
}

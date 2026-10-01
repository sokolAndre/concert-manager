<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reminder;
use App\Models\TelegramDestination;
use Illuminate\Support\Facades\DB;

class ReminderService
{
    public function create(
        Event $event,
        TelegramDestination $destination,
        int $offsetMinutes
    ): Reminder {
        if ($offsetMinutes <= 0) {
            throw new \InvalidArgumentException(
                'Reminder offset must be greater than zero.'
            );
        }

        if (!$destination->is_active) {
            throw new \InvalidArgumentException(
                'Telegram destination is inactive.'
            );
        }

        if (!$event->project
            ->telegramDestinations()
            ->whereKey($destination->id)
            ->exists()) {
            throw new \InvalidArgumentException(
                'Telegram destination is not linked to the event project.'
            );
        }

        return DB::transaction(function () use (
            $event,
            $destination,
            $offsetMinutes
        ) {
            return Reminder::create([
                'event_id' => $event->id,
                'telegram_destination_id' => $destination->id,
                'offset_minutes' => $offsetMinutes,

                // starts_at уже хранится/читается как UTC datetime.
                // copy() нужен, чтобы не изменять сам starts_at.
                'send_at' => $event->starts_at
                    ->copy()
                    ->subMinutes($offsetMinutes),
            ]);
        });
    }

    public function rescheduleForEvent(Event $event): int
    {
        $reminders = $event->reminders()
            ->whereNull('sent_at')
            ->get();

        foreach ($reminders as $reminder) {
            $reminder->update([
                'send_at' => $event->starts_at
                    ->copy()
                    ->subMinutes($reminder->offset_minutes),
            ]);
        }

        return $reminders->count();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send due event reminders to Telegram';

    public function handle(TelegramService $telegram): int
    {
        $reminders = Reminder::query()
            ->with([
                'event.project',
                'event.venue',
                'telegramDestination',
            ])
            ->whereNull('sent_at')
            ->where('send_at', '<=', now())
            ->orderBy('send_at')
            ->get();

        foreach ($reminders as $reminder) {
            $event = $reminder->event;
            $destination = $reminder->telegramDestination;

            if (! $destination->is_active) {
                continue;
            }

            $startsAt = $event->starts_at
                ->copy()
                ->timezone(config('app.display_timezone', 'Europe/Lisbon'));

            $text = implode("\n", array_filter([
                '🎵 Reminder',
                '',
                $event->project->name,
                $event->title,
                '🕒 '.$startsAt->format('d.m.Y H:i'),
                $event->venue
                    ? '📍 '.$event->venue->name
                    : ($event->location ? '📍 '.$event->location : null),
            ]));

            $telegram->sendMessage(
                $destination->telegram_chat_id,
                $text
            );

            $reminder->update([
                'sent_at' => now(),
            ]);

            $this->info("Reminder {$reminder->id} sent.");
        }

        $this->info("Processed {$reminders->count()} due reminder(s).");

        return self::SUCCESS;
    }
}

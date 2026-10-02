<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;

class TelegramCommandHandler
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly ScheduleService $schedule,
    ) {
    }

public function handle(array $message): void
{
    $chatId = $message['chat']['id'] ?? null;
    $text = trim($message['text'] ?? '');

    if (!$chatId || $text === '') {
        return;
    }

    match ($text) {
    '/start' => $this->handleStart($chatId),
            '/today' => $this->handleToday($chatId),
            '/week' => $this->handleWeek($chatId),
            '/month' => $this->handleMonth($chatId),
            '/upcoming' => $this->handleUpcoming($chatId),
            default => $this->handleUnknown($chatId),
        };
    }

private function handleStart(int|string $chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            "Concert Manager is ready.\n\n".
            "Available commands:\n".
            "/today — today's schedule\n".
            "/week — this week\n".
            "/month — this month\n".
            "/upcoming — upcoming events\n".
            "/start — show commands"
        );
    }

    private function handleToday(int|string $chatId): void
    {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');
        $now = now($timezone);

        $events = $this->schedule->between(
            $now->copy()->startOfDay(),
            $now->copy()->endOfDay(),
        );

        $this->sendSchedule(
            $chatId,
            "Today — {$now->format('d.m.Y')}",
            $events,
        );
    }

    private function handleWeek(int|string $chatId): void
    {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');
        $now = now($timezone);

        $from = $now->copy()->startOfWeek();
        $to = $now->copy()->endOfWeek();

        $events = $this->schedule->between($from, $to);

        $this->sendSchedule(
            $chatId,
            sprintf(
                'This week — %s–%s',
                $from->format('d.m'),
                $to->format('d.m.Y'),
            ),
            $events,
        );
    }

    private function handleMonth(int|string $chatId): void
    {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');
        $now = now($timezone);

        $events = $this->schedule->between(
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth(),
        );

        $this->sendSchedule(
            $chatId,
            $now->format('F Y'),
            $events,
        );
    }

    private function handleUpcoming(int|string $chatId): void
    {
        $events = $this->schedule->upcoming();

        $this->sendSchedule(
            $chatId,
            'Upcoming events',
            $events,
        );
    }

    private function sendSchedule(
    int|string $chatId,
        string $title,
        Collection $events,
    ): void {
    if ($events->isEmpty()) {
        $this->telegram->sendMessage(
            $chatId,
            "{$title}\n\nNo events."
        );

        return;
    }

    $timezone = config('app.display_timezone', 'Europe/Lisbon');

    $lines = [$title, ''];

    foreach ($events as $event) {
        $startsAt = $event->starts_at
            ->copy()
            ->timezone($timezone);

        $lines[] = sprintf(
            '%s — %s',
            $startsAt->format('d.m.Y H:i'),
            $event->title,
        );

        $lines[] = $event->project->name;

        $location = $event->venue?->name ?: $event->location;

            if ($location) {
                $lines[] = "📍 {$location}";
            }

            $lines[] = '';
        }

    $this->telegram->sendMessage(
        $chatId,
        trim(implode("\n", $lines)),
    );
}

    private function handleUnknown(int|string $chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            'Unknown command. Use /start to see available commands.'
        );
    }
}

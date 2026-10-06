<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Project;
use App\Models\TelegramConversation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class TelegramCommandHandler
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly ScheduleService $schedule,
        private readonly TelegramConversationService $conversations,
    ) {}

    public function handle(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if (! $chatId || $text === '') {
            return;
        }

        if ($text === '/cancel') {
            $this->handleCancel($chatId);

            return;
        }

        if ($text === '/add') {
            $this->handleAdd($chatId);

            return;
        }

        $conversation = $this->conversations->get($chatId);

        if ($conversation) {
            $this->handleConversation($chatId, $text, $conversation);

            return;
        }

        match ($text) {
            '/start' => $this->handleStart($chatId),
            '/today' => $this->handleToday($chatId),
            '/week' => $this->handleWeek($chatId),
            '/month' => $this->handleMonth($chatId),
            '/upcoming' => $this->handleUpcoming($chatId),
            '/events' => $this->handleEvents($chatId),
            default => $this->handleUnknown($chatId),
        };
    }

    private function handleEvents(
        int|string $chatId,
        string $mode = 'upcoming',
    ): void {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        if ($mode === 'cancelled') {
            $events = Event::query()
                ->with(['project', 'venue'])
                ->where('status', EventStatus::Cancelled)
                ->orderByDesc('starts_at')
                ->limit(20)
                ->get();

            $title = 'Cancelled events:';
        } else {
            $events = $this->schedule->upcoming(10);
            $title = 'Upcoming events:';
        }

        $buttons = [
            [
                [
                    'text' => $mode === 'upcoming'
                        ? '✓ Upcoming'
                        : 'Upcoming',
                    'callback_data' => 'events_upcoming',
                ],
                [
                    'text' => $mode === 'cancelled'
                        ? '✓ Cancelled'
                        : 'Cancelled',
                    'callback_data' => 'events_cancelled',
                ],
            ],
        ];

        foreach ($events as $event) {
            $startsAt = $event->starts_at
                ->copy()
                ->timezone($timezone);

            $buttons[] = [[
                'text' => sprintf(
                    '%s %s — %s',
                    $startsAt->format('d.m'),
                    $startsAt->format('H:i'),
                    $event->title,
                ),
                'callback_data' => 'event:'.$event->id,
            ]];
        }

        $text = $events->isEmpty()
            ? $title."\n\nNo events."
            : $title;

        $this->telegram->sendMessage(
            $chatId,
            $text,
            [
                'reply_markup' => [
                    'inline_keyboard' => $buttons,
                ],
            ],
        );
    }

    public function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryId = $callbackQuery['id'] ?? null;
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = $callbackQuery['data'] ?? null;

        if ($callbackQueryId) {
            try {
                $this->telegram->answerCallbackQuery(
                    $callbackQueryId,
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($chatId === null || $data === null) {
            return;
        }

        if ($messageId) {
            try {
                $this->telegram->removeInlineKeyboard(
                    $chatId,
                    $messageId,
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($data === 'cancel') {
            $this->handleCancel($chatId);

            return;
        }

        if (str_starts_with($data, 'add_project:')) {
            $projectNumber = substr($data, strlen('add_project:'));

            $conversation = $this->conversations->get($chatId);

            if (! $conversation || $conversation->state !== 'add_event_project') {
                $this->telegram->sendMessage(
                    $chatId,
                    'This action has expired. Please start again with /add.',
                );

                return;
            }

            $this->handleAddProject(
                $chatId,
                $projectNumber,
                $conversation,
            );
        }

        if (str_starts_with($data, 'add_type:')) {
            $type = substr($data, strlen('add_type:'));

            $conversation = $this->conversations->get($chatId);

            if (! $conversation || $conversation->state !== 'add_event_type') {
                $this->telegram->sendMessage(
                    $chatId,
                    'This action has expired. Please start again with /add.',
                );

                return;
            }

            $typeNumbers = [
                'concert' => '1',
                'rehearsal' => '2',
                'meeting' => '3',
                'other' => '4',
            ];

            if (! isset($typeNumbers[$type])) {
                return;
            }

            $this->handleAddEventType(
                $chatId,
                $typeNumbers[$type],
                $conversation,
            );
        }

        if ($data === 'add_save') {
            $conversation = $this->conversations->get($chatId);

            if (! $conversation || $conversation->state !== 'add_event_confirm') {
                $this->telegram->sendMessage(
                    $chatId,
                    'This action has expired. Please start again with /add.',
                );

                return;
            }

            $this->handleAddEventConfirm(
                $chatId,
                'save',
                $conversation,
            );

            return;
        }

        if (str_starts_with($data, 'event:')) {
            $eventId = substr($data, strlen('event:'));

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::with(['project', 'venue'])->find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            $this->showEvent($chatId, $event);

            return;
        }

        if ($data === 'events_upcoming') {
            $this->handleEvents(
                $chatId,
                'upcoming',
            );

            return;
        }

        if ($data === 'events_cancelled') {
            $this->handleEvents(
                $chatId,
                'cancelled',
            );

            return;
        }

        if ($data === 'events_back') {
            $this->handleEvents($chatId);

            return;
        }

        if (str_starts_with($data, 'event_cancel_confirm:')) {
            $eventId = substr(
                $data,
                strlen('event_cancel_confirm:'),
            );

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            $event->update([
                'status' => EventStatus::Cancelled,
            ]);

            $this->telegram->sendMessage(
                $chatId,
                "Event cancelled.\n\n".
                "{$event->title}",
                [
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'Back to events',
                                    'callback_data' => 'events_back',
                                ],
                            ],
                        ],
                    ],
                ],
            );

            return;
        }

        if (str_starts_with($data, 'event_cancel:')) {
            $eventId = substr($data, strlen('event_cancel:'));

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::with(['project', 'venue'])->find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            $this->showCancelEventConfirmation(
                $chatId,
                $event,
            );

            return;
        }

        if (str_starts_with($data, 'event_delete_confirm:')) {
            $eventId = substr(
                $data,
                strlen('event_delete_confirm:'),
            );

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            $title = $event->title;
            $wasCancelled = $event->status === EventStatus::Cancelled;

            $event->delete();

            $this->telegram->sendMessage(
                $chatId,
                "Event deleted.\n\n{$title}",
                [
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'Back to events',
                                    'callback_data' => $wasCancelled
                                        ? 'events_cancelled'
                                        : 'events_upcoming',
                                ],
                            ],
                        ],
                    ],
                ],
            );

            return;
        }

        if (str_starts_with($data, 'event_delete:')) {
            $eventId = substr(
                $data,
                strlen('event_delete:'),
            );

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::with('project')->find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            $this->showDeleteEventConfirmation(
                $chatId,
                $event,
            );

            return;
        }

        if (str_starts_with($data, 'event_restore:')) {
            $eventId = substr(
                $data,
                strlen('event_restore:'),
            );

            if (! ctype_digit($eventId)) {
                return;
            }

            $event = Event::find($eventId);

            if (! $event) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event no longer exists.',
                );

                return;
            }

            if ($event->status !== EventStatus::Cancelled) {
                $this->telegram->sendMessage(
                    $chatId,
                    'This event is not cancelled.',
                );

                return;
            }

            $event->update([
                'status' => EventStatus::Planned,
            ]);

            $this->telegram->sendMessage(
                $chatId,
                "Event restored.\n\n{$event->title}",
                [
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'View event',
                                    'callback_data' => 'event:'.$event->id,
                                ],
                            ],
                            [
                                [
                                    'text' => 'Back to events',
                                    'callback_data' => 'events_upcoming',
                                ],
                            ],
                        ],
                    ],
                ],
            );

            return;
        }
    }

    private function showDeleteEventConfirmation(
        int|string $chatId,
        Event $event,
    ): void {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        $startsAt = $event->starts_at
            ->copy()
            ->timezone($timezone);

        $this->telegram->sendMessage(
            $chatId,
            "Delete this event permanently?\n\n".
            "{$startsAt->format('d.m.Y H:i')} — {$event->title}\n".
            "{$event->project->name}\n\n".
            'This action cannot be undone.',
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Yes, delete',
                                'callback_data' => 'event_delete_confirm:'.$event->id,
                            ],
                        ],
                        [
                            [
                                'text' => 'Back',
                                'callback_data' => 'event:'.$event->id,
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    private function showCancelEventConfirmation(
        int|string $chatId,
        Event $event,
    ): void {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        $startsAt = $event->starts_at
            ->copy()
            ->timezone($timezone);

        $this->telegram->sendMessage(
            $chatId,
            "Cancel this event?\n\n".
            "{$startsAt->format('d.m.Y H:i')} — {$event->title}\n".
            $event->project->name,
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Yes, cancel event',
                                'callback_data' => 'event_cancel_confirm:'.$event->id,
                            ],
                        ],
                        [
                            [
                                'text' => 'Back',
                                'callback_data' => 'event:'.$event->id,
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    private function showEvent(
        int|string $chatId,
        Event $event,
    ): void {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        $startsAt = $event->starts_at
            ->copy()
            ->timezone($timezone);

        $location = $event->venue?->name
        ?? $event->location
        ?? '—';

        $text = "Event\n\n".
            "Project: {$event->project->name}\n".
            'Type: '.ucfirst($event->type->value)."\n".
            "Title: {$event->title}\n".
            'Date: '.$startsAt->format('d.m.Y')."\n".
            'Time: '.$startsAt->format('H:i')."\n".
            "Location: {$location}\n".
            'Status: '.ucfirst($event->status->value);

        if ($event->status === EventStatus::Cancelled) {
            $buttons = [
                [
                    [
                        'text' => 'Restore',
                        'callback_data' => 'event_restore:'.$event->id,
                    ],
                    [
                        'text' => 'Delete',
                        'callback_data' => 'event_delete:'.$event->id,
                    ],
                ],
                [
                    [
                        'text' => 'Back',
                        'callback_data' => 'events_cancelled',
                    ],
                ],
            ];
        } else {
            $buttons = [
                [
                    [
                        'text' => 'Edit',
                        'callback_data' => 'event_edit:'.$event->id,
                    ],
                    [
                        'text' => 'Cancel event',
                        'callback_data' => 'event_cancel:'.$event->id,
                    ],
                ],
                [
                    [
                        'text' => 'Delete',
                        'callback_data' => 'event_delete:'.$event->id,
                    ],
                ],
                [
                    [
                        'text' => 'Back',
                        'callback_data' => 'events_upcoming',
                    ],
                ],
            ];
        }

        $this->telegram->sendMessage(
            $chatId,
            $text,
            [
                'reply_markup' => [
                    'inline_keyboard' => $buttons,
                ],
            ],
        );
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
            "/add — add event\n".
            "/events — manage upcoming events\n".
            '/start — show commands'
        );
    }

    private function handleCancel(int|string $chatId): void
    {
        $conversation = $this->conversations->get($chatId);

        if (! $conversation) {
            $this->telegram->sendMessage(
                $chatId,
                'Nothing to cancel.'
            );

            return;
        }

        $this->conversations->clear($chatId);

        $this->telegram->sendMessage(
            $chatId,
            'Cancelled.'
        );
    }

    private function handleAdd(int|string $chatId): void
    {
        $projects = Project::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($projects->isEmpty()) {
            $this->telegram->sendMessage(
                $chatId,
                'No active projects found.'
            );

            return;
        }

        $projectIds = $projects->pluck('id')->values()->all();

        $this->conversations->start(
            $chatId,
            'add_event_project',
            [
                'project_ids' => $projectIds,
            ],
        );

        $buttons = [];

        foreach ($projects as $index => $project) {
            $buttons[] = [[
                'text' => $project->name,
                'callback_data' => 'add_project:'.($index + 1),
            ]];
        }

        $buttons[] = [[
            'text' => 'Cancel',
            'callback_data' => 'cancel',
        ]];

        $this->telegram->sendMessage(
            $chatId,
            'Choose project:',
            [
                'reply_markup' => [
                    'inline_keyboard' => $buttons,
                ],
            ],
        );
    }

    private function handleConversation(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        match ($conversation->state) {
            'add_event_project' => $this->handleAddProject(
                $chatId,
                $text,
                $conversation,
            ),
            'add_event_type' => $this->handleAddEventType(
                $chatId,
                $text,
                $conversation,
            ),
            'add_event_title' => $this->handleAddEventTitle(
                $chatId,
                $text,
                $conversation,
            ),
            'add_event_datetime' => $this->handleAddEventDateTime(
                $chatId,
                $text,
                $conversation,
            ),
            'add_event_location' => $this->handleAddEventLocation(
                $chatId,
                $text,
                $conversation,
            ),
            'add_event_confirm' => $this->handleAddEventConfirm(
                $chatId,
                $text,
                $conversation,
            ),

            default => $this->conversations->clear($chatId),
        };
    }

    private function handleAddEventConfirm(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        if (mb_strtolower(trim($text)) !== 'save') {
            $this->telegram->sendMessage(
                $chatId,
                'Send "save" to create the event or /cancel to cancel.',
            );

            return;
        }

        $data = $conversation->data;

        $project = Project::find($data['project_id']);

        if (! $project) {
            $this->conversations->clear($chatId);

            $this->telegram->sendMessage(
                $chatId,
                'Project no longer exists. Please start again with /add.',
            );

            return;
        }

        $event = Event::create([
            'project_id' => $project->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'starts_at' => Carbon::parse($data['starts_at'])->utc(),
            'location' => $data['location'],
            'status' => 'planned',
        ]);

        $this->conversations->clear($chatId);

        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        $startsAt = $event->starts_at
            ->copy()
            ->timezone($timezone);

        $this->telegram->sendMessage(
            $chatId,
            "Event created.\n\n".
            "{$startsAt->format('d.m.Y H:i')} — {$event->title}\n".
            "{$project->name}\n".
            "📍 {$event->location}",
        );
    }

    private function handleAddEventLocation(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        $location = trim($text);

        if (mb_strlen($location) < 2 || mb_strlen($location) > 255) {
            $this->telegram->sendMessage(
                $chatId,
                'Please enter a valid location.',
            );

            return;
        }

        $data = $conversation->data;
        $data['location'] = $location;

        $this->conversations->update(
            $conversation,
            'add_event_confirm',
            $data,
        );

        $project = Project::find($data['project_id']);

        if (! $project) {
            $this->conversations->clear($chatId);

            $this->telegram->sendMessage(
                $chatId,
                'Project no longer exists. Please start again with /add.',
            );

            return;
        }

        $timezone = config('app.display_timezone', 'Europe/Lisbon');

        $startsAt = Carbon::parse($data['starts_at'])
            ->timezone($timezone);

        $this->telegram->sendMessage(
            $chatId,
            "Create this event?\n\n".
            "Project: {$project->name}\n".
            'Type: '.ucfirst($data['type'])."\n".
            "Title: {$data['title']}\n".
            "Date: {$startsAt->format('d.m.Y')}\n".
            "Time: {$startsAt->format('H:i')}\n".
            "Location: {$location}",
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Save',
                                'callback_data' => 'add_save',
                            ],
                            [
                                'text' => 'Cancel',
                                'callback_data' => 'cancel',
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    private function handleAddEventDateTime(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        $timezone = config('app.display_timezone', 'Europe/Lisbon');
        $now = now($timezone);

        try {
            if (preg_match('/^\d{2}\.\d{2} \d{2}:\d{2}$/', $text)) {
                $startsAt = Carbon::createFromFormat(
                    '!d.m H:i',
                    $text,
                    $timezone,
                )->setYear($now->year);

                if ($startsAt->lt($now)) {
                    $startsAt->addYear();
                }
            } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/', $text)) {
                $startsAt = Carbon::createFromFormat(
                    '!d.m.Y H:i',
                    $text,
                    $timezone,
                );
            } else {
                throw new \InvalidArgumentException;
            }

            $errors = Carbon::getLastErrors();

            if (is_array($errors) && (
                $errors['warning_count'] > 0 ||
                $errors['error_count'] > 0
            )) {
                throw new \InvalidArgumentException;
            }
        } catch (Throwable) {
            $this->telegram->sendMessage(
                $chatId,
                "Invalid date or time.\n\n".
                "Use one of these formats:\n".
                "10.10 19:00\n".
                '10.10.2026 19:00',
            );

            return;
        }

        if ($startsAt->lt($now)) {
            $this->telegram->sendMessage(
                $chatId,
                'The event date is in the past. Please enter a future date.',
            );

            return;
        }

        $data = $conversation->data;

        // Store canonical UTC, not Madeira local time.
        $data['starts_at'] = $startsAt
            ->copy()
            ->utc()
            ->toIso8601String();

        $this->conversations->update(
            $conversation,
            'add_event_location',
            $data,
        );

        $this->telegram->sendMessage(
            $chatId,
            "Date: {$startsAt->format('d.m.Y')}\n".
            "Time: {$startsAt->format('H:i')}\n\n".
            "Send the location.\n".
            "For example: Praça do Povo\n\n".
            '/cancel — cancel',
        );
    }

    private function handleAddEventTitle(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        $title = trim($text);

        if (mb_strlen($title) < 2) {
            $this->telegram->sendMessage(
                $chatId,
                'The event title is too short. Please try again.',
            );

            return;
        }

        if (mb_strlen($title) > 255) {
            $this->telegram->sendMessage(
                $chatId,
                'The event title is too long. Maximum 255 characters.',
            );

            return;
        }

        $data = $conversation->data;
        $data['title'] = $title;

        $this->conversations->update(
            $conversation,
            'add_event_datetime',
            $data,
        );

        $this->telegram->sendMessage(
            $chatId,
            "Title: {$title}\n\n".
            "Send the date and time in this format:\n".
            "10.10 19:00\n\n".
            '/cancel — cancel',
        );
    }

    private function handleAddProject(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        if (! ctype_digit($text)) {
            $this->telegram->sendMessage(
                $chatId,
                'Please send the project number.'
            );

            return;
        }

        $index = (int) $text - 1;
        $projectIds = $conversation->data['project_ids'] ?? [];
        $projectId = $projectIds[$index] ?? null;

        if (! $projectId) {
            $this->telegram->sendMessage(
                $chatId,
                'Invalid project number. Please try again.'
            );

            return;
        }

        $project = Project::find($projectId);

        if (! $project) {
            $this->conversations->clear($chatId);

            $this->telegram->sendMessage(
                $chatId,
                'Project no longer exists. Please start again with /add.'
            );

            return;
        }

        $this->conversations->update(
            $conversation,
            'add_event_type',
            [
                'project_id' => $project->id,
            ],
        );

        $this->telegram->sendMessage(
            $chatId,
            "Project: {$project->name}\n\n".
            'Choose event type:',
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Concert',
                                'callback_data' => 'add_type:concert',
                            ],
                            [
                                'text' => 'Rehearsal',
                                'callback_data' => 'add_type:rehearsal',
                            ],
                        ],
                        [
                            [
                                'text' => 'Meeting',
                                'callback_data' => 'add_type:meeting',
                            ],
                            [
                                'text' => 'Other',
                                'callback_data' => 'add_type:other',
                            ],
                        ],
                        [
                            [
                                'text' => 'Cancel',
                                'callback_data' => 'cancel',
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    private function handleAddEventType(
        int|string $chatId,
        string $text,
        TelegramConversation $conversation,
    ): void {
        $types = [
            '1' => 'concert',
            '2' => 'rehearsal',
            '3' => 'meeting',
            '4' => 'other',
        ];

        $type = $types[$text] ?? null;

        if (! $type) {
            $this->telegram->sendMessage(
                $chatId,
                "Please choose a valid event type:\n".
                "1. Concert\n".
                "2. Rehearsal\n".
                "3. Meeting\n".
                '4. Other'
            );

            return;
        }

        $data = $conversation->data;
        $data['type'] = $type;

        $this->conversations->update(
            $conversation,
            'add_event_title',
            $data,
        );

        $this->telegram->sendMessage(
            $chatId,
            'Type: '.ucfirst($type)."\n\n".
            "Send the event title.\n".
            '/cancel — cancel'
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

<?php

namespace App\Services;

use App\Models\TelegramConversation;

class TelegramConversationService
{
    public function get(int|string $chatId): ?TelegramConversation
    {
        return TelegramConversation::where(
            'telegram_chat_id',
            $chatId
        )->first();
    }

    public function start(
        int|string $chatId,
        string $state,
        array $data = [],
    ): TelegramConversation {
        return TelegramConversation::updateOrCreate(
            ['telegram_chat_id' => $chatId],
            [
                'state' => $state,
                'data' => $data,
            ],
        );
    }

    public function update(
        TelegramConversation $conversation,
        string $state,
        array $data,
    ): TelegramConversation {
        $conversation->update([
            'state' => $state,
            'data' => $data,
        ]);

        return $conversation->refresh();
    }

    public function clear(int|string $chatId): void
    {
        TelegramConversation::where(
            'telegram_chat_id',
            $chatId
        )->delete();
    }
}

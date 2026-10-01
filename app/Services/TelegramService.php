<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    public function sendMessage(
        int|string $chatId,
        string $text
    ): array {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/sendMessage",
            [
                'chat_id' => $chatId,
                'text' => $text,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: ' . $response->body()
            );
        }

        return $response->json();
    }

    public function getUpdates(): array
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::get(
            "https://api.telegram.org/bot{$token}/getUpdates"
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: ' . $response->body()
            );
        }

        return $response->json();
    }

}

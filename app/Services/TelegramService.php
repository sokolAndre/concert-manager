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

    public function getUpdates(?int $offset = null): array
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $params = [
            'timeout' => 30,
        ];

        if ($offset !== null) {
            $params['offset'] = $offset;
        }

        $response = Http::timeout(35)->get(
            "https://api.telegram.org/bot{$token}/getUpdates",
            $params
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: '.$response->body()
            );
        }

        return $response->json();
    }

}

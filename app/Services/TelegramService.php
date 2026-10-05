<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    public function sendMessage(
        int|string $chatId,
        string $text,
        array $options = [],
    ): array {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/sendMessage",
            array_merge([
                'chat_id' => $chatId,
                'text' => $text,
            ], $options),
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: '.$response->body(),
            );
        }

        return $response->json();
    }

    public function getUpdates(?int $offset = null): array
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
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

    public function removeInlineKeyboard(
        int|string $chatId,
        int $messageId,
    ): array {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/editMessageReplyMarkup",
            [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => [
                    'inline_keyboard' => [],
                ],
            ],
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: '.$response->body(),
            );
        }

        return $response->json();
    }

    public function answerCallbackQuery(string $callbackQueryId): array
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/answerCallbackQuery",
            [
                'callback_query_id' => $callbackQueryId,
            ],
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram API error: '.$response->body(),
            );
        }

        return $response->json();
    }
}

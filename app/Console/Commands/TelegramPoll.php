<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use App\Services\TelegramCommandHandler;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll';

    protected $description = 'Listen for incoming Telegram bot updates';

    public function handle(
        TelegramService $telegram,
        TelegramCommandHandler $handler,
    ): int {
        $this->info('Telegram polling started.');

        $offset = null;

        while (true) {
            $updates = $telegram->getUpdates($offset);

            foreach ($updates['result'] ?? [] as $update) {
                $offset = $update['update_id'] + 1;

                $message = $update['message'] ?? null;

                if (!$message) {
                    continue;
                }

                $text = trim($message['text'] ?? '');
                $chatId = $message['chat']['id'] ?? null;

                $this->line("Received from {$chatId}: {$text}");

                $handler->handle($message);
            }
        }
    }
}

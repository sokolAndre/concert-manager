<?php

namespace App\Console\Commands;

use App\Services\TelegramCommandHandler;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Throwable;

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
            try {
                $updates = $telegram->getUpdates($offset);

                foreach ($updates['result'] ?? [] as $update) {
                    $offset = $update['update_id'] + 1;

                    if (isset($update['callback_query'])) {
                        $handler->handleCallbackQuery(
                            $update['callback_query'],
                        );

                        continue;
                    }

                    if (isset($update['message'])) {
                        $message = $update['message'];

                        $this->info(
                            'Chat ID: '.($message['chat']['id'] ?? 'unknown').
                            ' | Type: '.($message['chat']['type'] ?? 'unknown').
                            ' | Title: '.($message['chat']['title'] ?? 'private')
                        );

                        $handler->handle($message);
                    }
                }
            } catch (Throwable $exception) {
                $this->error(
                    'Telegram polling error: '.$exception->getMessage(),
                );

                sleep(5);
            }
        }
    }
}

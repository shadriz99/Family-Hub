<?php

declare(strict_types=1);

namespace App\Modules\Finance\Console\Commands;

use App\Modules\Finance\Services\TelegramBotService;
use Illuminate\Console\Command;

final class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Run a single polling cycle and exit} {--timeout=20 : Long polling timeout in seconds}';

    protected $description = 'Poll for incoming Telegram bot updates on local or non-webhook ODROID environments';

    public function handle(TelegramBotService $botService): int
    {
        $this->info('Starting Telegram Bot Poller for Family Hub (ODROID)...');
        $this->info('Press Ctrl+C to stop.');

        $offset = 0;
        $timeout = (int) $this->option('timeout');
        $once = (bool) $this->option('once');

        $running = true;
        if (extension_loaded('pcntl')) {
            $this->trap([SIGINT, SIGTERM], function () use (&$running): void {
                $this->warn("\nStopping Telegram poller...");
                $running = false;
            });
        }

        while ($running) {
            $updates = $botService->getUpdates($offset, 100, $timeout);

            foreach ($updates as $update) {
                $updateId = $update['update_id'] ?? 0;
                if ($updateId >= $offset) {
                    $offset = $updateId + 1;
                }

                $res = $botService->handleUpdate($update);
                $this->line(sprintf('[%s] Update #%d: %s (%s)', now()->format('H:i:s'), $updateId, $res['status'], $res['message']));
            }

            if ($once) {
                break;
            }

            if (empty($updates)) {
                // Short sleep between empty polls if timeout returns early
                usleep(500000); // 0.5s
            }
        }

        $this->info('Poller stopped.');

        return self::SUCCESS;
    }
}

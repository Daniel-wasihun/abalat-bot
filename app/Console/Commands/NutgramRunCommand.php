<?php

namespace App\Console\Commands;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\SingleUpdate;

class NutgramRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nutgram:run {--once} {--pollingTimeout=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the bot in long polling mode (graceful timeout handling)';

    /**
     * Execute the console command.
     */
    public function handle(Nutgram $bot): void
    {
        if ($pollingTimeout = $this->option('pollingTimeout')) {
            config()?->set('nutgram.config.polling.timeout', (int)$pollingTimeout);
        }

        if ($this->option('once')) {
            $bot->setRunningMode(SingleUpdate::class);
        }

        try {
            $bot->run();
        } catch (ConnectException $e) {
            // Silently suppress cURL timeout errors to prevent stack trace pollution.
            // The command will exit cleanly with status 0, allowing loops to restart it smoothly.
            if (!str_contains($e->getMessage(), 'cURL error 28')) {
                $this->warn("Telegram connection dropped: " . $e->getMessage());
            }
            return;
        }
    }
}

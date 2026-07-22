<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the Telegram bot webhook URL with Telegram API';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $botService)
    {
        $webhookUrl = env('TELEGRAM_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            $this->error('TELEGRAM_WEBHOOK_URL is not set in your .env file.');
            return Command::FAILURE;
        }

        $this->info("Setting Telegram Webhook URL to: {$webhookUrl}");

        try {
            $result = $botService->setWebhook($webhookUrl);
            
            if (isset($result['ok']) && $result['ok']) {
                $this->info('Webhook set successfully!');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            } else {
                $this->error('Failed to set webhook.');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Error registering webhook: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

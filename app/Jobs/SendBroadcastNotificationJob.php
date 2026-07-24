<?php

namespace App\Jobs;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $notificationId;
    protected array $recipients;
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $notificationId, array $recipients, string $message)
    {
        $this->notificationId = $notificationId;
        $this->recipients = $recipients;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(
        NotificationRepositoryInterface $notificationRepo,
        TelegramBotService $botService
    ): void {
        $notification = $notificationRepo->findById($this->notificationId);
        if (!$notification) {
            Log::error("SendBroadcastNotificationJob: Notification with ID {$this->notificationId} not found.");
            return;
        }

        $notificationRepo->updateStatus($this->notificationId, 'Processing');

        $sentCount = 0;
        $failedCount = 0;

        foreach ($this->recipients as $user) {
            $chatId = $user['chatId'] ?? null;
            $userId = $user['id'] ?? '';
            $telegramId = $user['telegramId'] ?? '';

            if (!$chatId) {
                $failedCount++;
                $notificationRepo->logDelivery([
                    'notificationId' => $this->notificationId,
                    'userId' => $userId,
                    'telegramId' => $telegramId,
                    'status' => 'Failed',
                    'error' => 'Missing chat ID',
                ]);
                continue;
            }

            // Format message with language-tailored Sunday School header
            $userLang = $user['preferredLanguage'] ?? $user['language'] ?? 'am';
            $title    = $notification['title'] ?? 'Announcement';

            $headerLabel = match($userLang) {
                'om'    => "📢 Beeksisa",
                'en'    => "📢 Announcement",
                default => "📢 ማስታወቂያ",
            };

            $footerLabel = match($userLang) {
                'om'    => "M.B.D. Daqiiqaa Birhaan 🙏",
                'en'    => "Dekiqen Birhan Sunday School 🙏",
                default => "ደቂቀ ብርሃን ሰንበት ትምህርት ቤት 🙏",
            };

            $formattedMessage = "{$headerLabel}\n" .
                "————————————————————\n" .
                "{$title}\n\n" .
                "{$this->message}\n" .
                "————————————————————\n" .
                "{$footerLabel}";

            // Call bot service to send message
            $success = $botService->sendMessage($chatId, $formattedMessage);

            if ($success) {
                $sentCount++;
                $notificationRepo->logDelivery([
                    'notificationId' => $this->notificationId,
                    'userId' => $userId,
                    'telegramId' => $telegramId,
                    'status' => 'Success',
                ]);
            } else {
                $failedCount++;
                $notificationRepo->logDelivery([
                    'notificationId' => $this->notificationId,
                    'userId' => $userId,
                    'telegramId' => $telegramId,
                    'status' => 'Failed',
                    'error' => 'Telegram API Send Failed or Rate Limited',
                ]);
            }

            // Update stats dynamically in Firestore
            $notificationRepo->updateStatus($this->notificationId, 'Processing', [
                'sentCount' => $sentCount,
                'failedCount' => $failedCount,
            ]);

            // Rate limiting spacing (max 30 messages/sec limit by Telegram, sleep 40ms to be safe)
            usleep(40000);
        }

        $finalStatus = ($sentCount > 0) ? 'Completed' : 'Failed';
        $notificationRepo->updateStatus($this->notificationId, $finalStatus, [
            'sentCount' => $sentCount,
            'failedCount' => $failedCount,
        ]);
    }
}

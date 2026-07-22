<?php

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Jobs\SendBroadcastNotificationJob;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected NotificationRepositoryInterface $notificationRepo;
    protected UserRepositoryInterface $userRepo;
    protected FeedbackRepositoryInterface $feedbackRepo;

    public function __construct(
        NotificationRepositoryInterface $notificationRepo,
        UserRepositoryInterface $userRepo,
        FeedbackRepositoryInterface $feedbackRepo
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->userRepo = $userRepo;
        $this->feedbackRepo = $feedbackRepo;
    }

    public function estimateRecipients(string $targetType, $targetValue = null): array
    {
        $recipients = $this->getTargetRecipients($targetType, $targetValue);
        $count = count($recipients);
        // Telegram rate limit estimation (~25-30 msgs per sec)
        $estimatedSeconds = ceil($count / 25);

        return [
            'count' => $count,
            'estimatedSeconds' => $estimatedSeconds,
            'estimatedDurationFormatted' => $estimatedSeconds < 60 
                ? "{$estimatedSeconds} seconds" 
                : ceil($estimatedSeconds / 60) . " minutes",
        ];
    }

    public function createAndBroadcast(array $data, string $sentBy): array
    {
        $recipients = $this->getTargetRecipients($data['targetType'] ?? 'all', $data['targetValue'] ?? null);
        $totalRecipients = count($recipients);

        $notification = $this->notificationRepo->createNotification([
            'title' => $data['title'],
            'message' => $data['message'],
            'sentBy' => $sentBy,
            'targetType' => $data['targetType'] ?? 'all',
            'targetValue' => $data['targetValue'] ?? null,
            'scheduledAt' => $data['scheduledAt'] ?? null,
            'totalRecipients' => $totalRecipients,
            'status' => !empty($data['scheduledAt']) ? 'Scheduled' : 'Processing',
        ]);

        if (empty($data['scheduledAt'])) {
            // Dispatch async job or sync execution based on queue config
            SendBroadcastNotificationJob::dispatch($notification['id'], $recipients, $data['message']);
        }

        return $notification;
    }

    public function getTargetRecipients(string $targetType, $targetValue = null): array
    {
        $allUsers = $this->userRepo->getAll();

        switch ($targetType) {
            case 'active':
                return array_values(array_filter($allUsers, fn($u) => ($u['active'] ?? true) === true));

            case 'selected':
                $selectedIds = is_array($targetValue) ? $targetValue : explode(',', (string) $targetValue);
                return array_values(array_filter($allUsers, fn($u) => in_array($u['id'], $selectedIds) || in_array($u['telegramId'], $selectedIds)));

            case 'category':
                $feedbacks = $this->feedbackRepo->getAll(['category' => $targetValue]);
                $userMap = [];
                foreach ($feedbacks as $f) {
                    if (!empty($f['userId'])) {
                        $userMap[$f['userId']] = true;
                    }
                }
                return array_values(array_filter($allUsers, fn($u) => isset($userMap[$u['id']])));

            case 'all':
            default:
                return array_values($allUsers);
        }
    }

    public function getNotificationsList(): array
    {
        return $this->notificationRepo->getAll();
    }

    public function getNotificationLogs(string $id): array
    {
        return $this->notificationRepo->getLogsByNotificationId($id);
    }
}

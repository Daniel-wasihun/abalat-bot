<?php

namespace App\Repositories\Firestore;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\FirestoreService;

class FirestoreNotificationRepository implements NotificationRepositoryInterface
{
    protected FirestoreService $firestore;
    protected string $collection = 'notifications';
    protected string $logsCollection = 'notification_logs';

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function createNotification(array $data): array
    {
        $now = now()->toIso8601String();
        $payload = array_merge([
            'status' => 'Pending',
            'totalRecipients' => 0,
            'sentCount' => 0,
            'failedCount' => 0,
            'createdAt' => $now,
        ], $data);

        $result = $this->firestore->collection($this->collection)->add($payload);
        return $result['data'];
    }

    public function findById(string $id): ?array
    {
        return $this->firestore->collection($this->collection)->doc($id)->get();
    }

    public function getAll(): array
    {
        $items = $this->firestore->collection($this->collection)->get();
        usort($items, fn($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));
        return array_values($items);
    }

    public function updateStatus(string $id, string $status, array $metrics = []): void
    {
        $payload = array_merge(['status' => $status], $metrics);
        $this->firestore->collection($this->collection)->doc($id)->update($payload);
    }

    public function logDelivery(array $logData): void
    {
        $now = now()->toIso8601String();
        $payload = array_merge([
            'sentAt' => $now,
        ], $logData);

        $this->firestore->collection($this->logsCollection)->add($payload);
    }

    public function getLogsByNotificationId(string $notificationId): array
    {
        $logs = $this->firestore->collection($this->logsCollection)->where('notificationId', '=', $notificationId);
        usort($logs, fn($a, $b) => strcmp($b['sentAt'] ?? '', $a['sentAt'] ?? ''));
        return array_values($logs);
    }

    public function getLogsByUserId(string $userId): array
    {
        $logs = $this->firestore->collection($this->logsCollection)->where('userId', '=', $userId);
        usort($logs, fn($a, $b) => strcmp($b['sentAt'] ?? '', $a['sentAt'] ?? ''));
        return array_values($logs);
    }

    public function getStats(): array
    {
        $all = $this->firestore->collection($this->collection)->get();
        $logs = $this->firestore->collection($this->logsCollection)->get();

        $totalBroadcasts = count($all);
        $totalSent = count(array_filter($logs, fn($l) => ($l['status'] ?? '') === 'Success'));
        $totalFailed = count(array_filter($logs, fn($l) => ($l['status'] ?? '') === 'Failed'));

        return [
            'totalBroadcasts' => $totalBroadcasts,
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
        ];
    }
}

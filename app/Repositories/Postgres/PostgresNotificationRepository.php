<?php

namespace App\Repositories\Postgres;

use App\Models\BotNotification;
use App\Models\NotificationDeliveryLog;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class PostgresNotificationRepository implements NotificationRepositoryInterface
{
    public function createNotification(array $data): array
    {
        $payload = array_merge([
            'status' => 'Pending',
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ], [
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'sent_by' => $data['sentBy'] ?? null,
            'target_type' => $data['targetType'] ?? 'all',
            'target_value' => $data['targetValue'] ?? null,
            'total_recipients' => $data['totalRecipients'] ?? 0,
            'scheduled_at' => $data['scheduledAt'] ?? null,
        ]);

        $notification = BotNotification::create($payload);
        return $notification->fresh()->toApiArray();
    }

    public function findById(string $id): ?array
    {
        $notification = BotNotification::find($id);
        return $notification ? $notification->toApiArray() : null;
    }

    public function getAll(array $filters = []): array
    {
        $query = BotNotification::query();
        
        if (!empty($filters['search'])) {
            $s = strtolower($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(message) LIKE ?', ["%{$s}%"]);
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['sort_by'])) {
            $dir = (!empty($filters['sort_order']) && strtolower($filters['sort_order']) === 'asc') ? 'asc' : 'desc';
            $col = $filters['sort_by'];
            if ($col === 'scheduledAt') $col = 'scheduled_at';
            if ($col === 'targetType') $col = 'target_type';
            if ($col === 'createdAt') $col = 'created_at';
            
            $query->orderBy($col, $dir);
        } else {
            $query->chronological();
        }

        return $query->get()->map->toApiArray()->all();
    }

    public function updateStatus(string $id, string $status, array $metrics = []): void
    {
        $payload = ['status' => $status];
        if (isset($metrics['sentCount'])) {
            $payload['sent_count'] = $metrics['sentCount'];
        }
        if (isset($metrics['failedCount'])) {
            $payload['failed_count'] = $metrics['failedCount'];
        }
        
        BotNotification::where('id', $id)->update($payload);
    }

    public function logDelivery(array $logData): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $logData['notificationId'],
            'telegram_user_id' => $logData['userId'] ?? null,
            'telegram_id' => $logData['telegramId'] ?? null,
            'status' => $logData['status'] ?? 'Pending',
            'error_message' => $logData['error'] ?? null,
            'sent_at' => now(),
        ]);
    }

    public function getLogsByNotificationId(string $notificationId): array
    {
        return NotificationDeliveryLog::where('notification_id', $notificationId)
            ->latest('sent_at')
            ->get()
            ->map->toApiArray()
            ->all();
    }

    public function getLogsByUserId(string $userId): array
    {
        return NotificationDeliveryLog::where('telegram_user_id', $userId)
            ->latest('sent_at')
            ->get()
            ->map->toApiArray()
            ->all();
    }

    public function getStats(): array
    {
        $totalBroadcasts = BotNotification::count();
        $totalSent = NotificationDeliveryLog::where('status', 'Success')->count();
        $totalFailed = NotificationDeliveryLog::where('status', 'Failed')->count();

        return [
            'totalBroadcasts' => $totalBroadcasts,
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
        ];
    }
}

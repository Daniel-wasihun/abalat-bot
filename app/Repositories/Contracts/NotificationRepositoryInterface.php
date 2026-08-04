<?php

namespace App\Repositories\Contracts;

interface NotificationRepositoryInterface
{
    public function createNotification(array $data): array;
    public function findById(string $id): ?array;
    public function getAll(array $filters = []): array;
    public function updateStatus(string $id, string $status, array $metrics = []): void;
    public function logDelivery(array $logData): void;
    public function getLogsByNotificationId(string $notificationId): array;
    public function getLogsByUserId(string $userId): array;
    public function getStats(): array;
}

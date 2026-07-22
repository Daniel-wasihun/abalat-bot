<?php

namespace App\Repositories\Contracts;

interface NotificationRepositoryInterface
{
    public function createNotification(array $data): array;
    public function findById(string $id): ?array;
    public function getAll(): array;
    public function updateStatus(string $id, string $status, array $metrics = []): void;
    public function logDelivery(array $logData): void;
    public function getLogsByNotificationId(string $notificationId): array;
    public function getStats(): array;
}

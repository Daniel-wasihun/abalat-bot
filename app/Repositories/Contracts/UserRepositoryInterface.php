<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function findByTelegramId(string $telegramId): ?array;
    public function createOrUpdateTelegramUser(array $telegramData): array;
    public function getAll(array $filters = []): array;
    public function findById(string $id): ?array;
    public function update(string $id, array $data): void;
    public function getActiveUsersCount(): int;
    public function getTotalUsersCount(): int;
}

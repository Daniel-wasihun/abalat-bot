<?php

namespace App\Repositories\Firestore;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\FirestoreService;

class FirestoreUserRepository implements UserRepositoryInterface
{
    protected FirestoreService $firestore;
    protected string $collection = 'users';

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function findByTelegramId(string $telegramId): ?array
    {
        $users = $this->firestore->collection($this->collection)->where('telegramId', '=', (string) $telegramId);
        return $users[0] ?? null;
    }

    public function createOrUpdateTelegramUser(array $telegramData): array
    {
        $telegramId = (string) $telegramData['telegramId'];
        $existing = $this->findByTelegramId($telegramId);
        $now = now()->toIso8601String();

        if ($existing) {
            $updatePayload = array_merge($telegramData, [
                'lastActivity' => $now,
                'updatedAt' => $now,
            ]);
            $this->firestore->collection($this->collection)->doc($existing['id'])->update($updatePayload);
            return array_merge($existing, $updatePayload);
        } else {
            $userPayload = array_merge($telegramData, [
                'joinedAt' => $now,
                'lastActivity' => $now,
                'active' => true,
                'createdAt' => $now,
                'updatedAt' => $now,
            ]);
            $result = $this->firestore->collection($this->collection)->add($userPayload);
            return $result['data'];
        }
    }

    public function getAll(array $filters = []): array
    {
        $users = $this->firestore->collection($this->collection)->get();

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $users = array_filter($users, function ($u) use ($search) {
                return str_contains(strtolower($u['firstName'] ?? ''), $search) ||
                       str_contains(strtolower($u['lastName'] ?? ''), $search) ||
                       str_contains(strtolower($u['username'] ?? ''), $search) ||
                       str_contains(strtolower((string)($u['telegramId'] ?? '')), $search);
            });
        }

        if (isset($filters['active'])) {
            $activeBool = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN);
            $users = array_filter($users, fn($u) => ($u['active'] ?? true) === $activeBool);
        }

        // Sort by joinedAt desc
        usort($users, fn($a, $b) => strcmp($b['joinedAt'] ?? '', $a['joinedAt'] ?? ''));

        return array_values($users);
    }

    public function findById(string $id): ?array
    {
        return $this->firestore->collection($this->collection)->doc($id)->get();
    }

    public function update(string $id, array $data): void
    {
        $this->firestore->collection($this->collection)->doc($id)->update($data);
    }

    public function getActiveUsersCount(): int
    {
        $all = $this->firestore->collection($this->collection)->get();
        return count(array_filter($all, fn($u) => ($u['active'] ?? true) === true));
    }

    public function getTotalUsersCount(): int
    {
        return count($this->firestore->collection($this->collection)->get());
    }
}

<?php

namespace App\Repositories\Postgres;

use App\Models\TelegramUser;
use App\Repositories\Contracts\UserRepositoryInterface;

class PostgresUserRepository implements UserRepositoryInterface
{
    public function findByTelegramId(string $telegramId): ?array
    {
        $user = TelegramUser::where('telegram_id', $telegramId)->first();
        return $user ? $user->toApiArray() : null;
    }

    public function createOrUpdateTelegramUser(array $telegramData): array
    {
        $user = TelegramUser::updateOrCreate(
            ['telegram_id' => $telegramData['telegramId']],
            [
                'first_name' => $telegramData['firstName'] ?? null,
                'last_name' => $telegramData['lastName'] ?? null,
                'username' => $telegramData['username'] ?? null,
                'chat_id' => $telegramData['chatId'] ?? null,
                'preferred_language' => $telegramData['preferredLanguage'] ?? 'am',
                'language' => $telegramData['language'] ?? 'am', // backward-compat
                'last_activity_at' => now(),
            ]
        );

        return $user->toApiArray();
    }

    public function getAll(array $filters = []): array
    {
        $query = TelegramUser::query();

        if (!empty($filters['search'])) {
            $s = strtolower($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%{$s}%"])
                  ->orWhere('telegram_id', 'LIKE', "%{$s}%");
            });
        }

        if (isset($filters['active'])) {
            $activeBool = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $activeBool);
        }

        if (!empty($filters['sort_by'])) {
            $dir = (!empty($filters['sort_order']) && strtolower($filters['sort_order']) === 'asc') ? 'asc' : 'desc';
            $col = $filters['sort_by'];
            if ($col === 'telegramId') $col = 'telegram_id';
            if ($col === 'firstName') $col = 'first_name';
            if ($col === 'lastName') $col = 'last_name';
            if ($col === 'lastActivity') $col = 'last_activity_at';
            if ($col === 'createdAt') $col = 'created_at';
            
            $query->orderBy($col, $dir);
        } else {
            $query->latest();
        }

        return $query->get()->map->toApiArray()->all();
    }

    public function findById(string $id): ?array
    {
        $user = TelegramUser::find($id);
        return $user ? $user->toApiArray() : null;
    }

    public function update(string $id, array $data): void
    {
        $updatePayload = [];
        if (isset($data['preferredLanguage'])) $updatePayload['preferred_language'] = $data['preferredLanguage'];
        if (isset($data['language'])) $updatePayload['language'] = $data['language'];
        if (isset($data['active'])) $updatePayload['active'] = $data['active'];

        if (!empty($updatePayload)) {
            TelegramUser::where('id', $id)->update($updatePayload);
        }
    }

    public function getActiveUsersCount(): int
    {
        return TelegramUser::where('active', true)->count();
    }

    public function getTotalUsersCount(): int
    {
        return TelegramUser::count();
    }
}

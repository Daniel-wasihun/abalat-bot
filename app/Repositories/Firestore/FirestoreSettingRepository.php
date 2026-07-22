<?php

namespace App\Repositories\Firestore;

use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\FirestoreService;

class FirestoreSettingRepository implements SettingRepositoryInterface
{
    protected FirestoreService $firestore;
    protected string $collection = 'settings';

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function get(string $key, $default = null)
    {
        $doc = $this->firestore->collection($this->collection)->doc($key)->get();
        return $doc['value'] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->firestore->collection($this->collection)->doc($key)->set([
            'key' => $key,
            'value' => $value,
            'updatedAt' => now()->toIso8601String(),
        ], true);
    }

    public function getAll(): array
    {
        $all = $this->firestore->collection($this->collection)->get();
        $settings = [];
        foreach ($all as $item) {
            if (isset($item['key'])) {
                $settings[$item['key']] = $item['value'] ?? null;
            }
        }
        return $settings;
    }
}

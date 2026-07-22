<?php

namespace App\Repositories\Firestore;

use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\FirestoreService;

class FirestoreAdminRepository implements AdminRepositoryInterface
{
    protected FirestoreService $firestore;
    protected string $collection = 'admins';

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function findByEmail(string $email): ?array
    {
        $admins = $this->firestore->collection($this->collection)->where('email', '=', strtolower(trim($email)));
        return $admins[0] ?? null;
    }

    public function findById(string $id): ?array
    {
        return $this->firestore->collection($this->collection)->doc($id)->get();
    }

    public function create(array $data): array
    {
        $now = now()->toIso8601String();
        $data['email'] = strtolower(trim($data['email']));
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        if (!isset($data['role'])) {
            $data['role'] = 'Admin';
        }
        $res = $this->firestore->collection($this->collection)->add($data);
        return $res['data'];
    }

    public function update(string $id, array $data): void
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }
        $data['updatedAt'] = now()->toIso8601String();
        $this->firestore->collection($this->collection)->doc($id)->update($data);
    }

    public function delete(string $id): void
    {
        $this->firestore->collection($this->collection)->doc($id)->delete();
    }

    public function getAll(): array
    {
        $admins = $this->firestore->collection($this->collection)->get();
        // Exclude password hash from list results for safety
        return array_map(function ($a) {
            unset($a['password']);
            return $a;
        }, $admins);
    }
}

<?php

namespace App\Repositories\Contracts;

interface AdminRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function findById(string $id): ?array;
    public function create(array $data): array;
    public function update(string $id, array $data): void;
    public function delete(string $id): void;
    public function getAll(): array;
}

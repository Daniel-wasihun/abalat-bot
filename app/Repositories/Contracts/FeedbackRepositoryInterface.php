<?php

namespace App\Repositories\Contracts;

interface FeedbackRepositoryInterface
{
    public function create(array $data): array;
    public function findById(string $id): ?array;
    public function getAll(array $filters = []): array;
    public function getByUserId(string $userId): array;
    public function updateStatus(string $id, string $status): void;
    public function updatePriority(string $id, string $priority): void;
    public function updateCategory(string $id, string $category): void;
    public function addInternalNote(string $id, string $note, string $author): void;
    public function delete(string $id): void;
    public function getStats(): array;
}

<?php

namespace App\Repositories\Firestore;

use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Services\FirestoreService;

class FirestoreFeedbackRepository implements FeedbackRepositoryInterface
{
    protected FirestoreService $firestore;
    protected string $collection = 'feedback';

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function create(array $data): array
    {
        $now = now()->toIso8601String();
        $payload = array_merge([
            'status' => 'New',
            'priority' => 'Medium',
            'category' => 'Other',
            'internalNotes' => [],
            'createdAt' => $now,
            'updatedAt' => $now,
        ], $data);

        $result = $this->firestore->collection($this->collection)->add($payload);
        return $result['data'];
    }

    public function findById(string $id): ?array
    {
        return $this->firestore->collection($this->collection)->doc($id)->get();
    }

    public function getAll(array $filters = []): array
    {
        $items = $this->firestore->collection($this->collection)->get();

        if (!empty($filters['status'])) {
            $items = array_filter($items, fn($i) => strtolower($i['status'] ?? '') === strtolower($filters['status']));
        }

        if (!empty($filters['priority'])) {
            $items = array_filter($items, fn($i) => strtolower($i['priority'] ?? '') === strtolower($filters['priority']));
        }

        if (!empty($filters['category'])) {
            $items = array_filter($items, fn($i) => strtolower($i['category'] ?? '') === strtolower($filters['category']));
        }

        if (!empty($filters['search'])) {
            $s = strtolower($filters['search']);
            $items = array_filter($items, function ($i) use ($s) {
                return str_contains(strtolower($i['message'] ?? ''), $s) ||
                       str_contains(strtolower($i['userName'] ?? ''), $s) ||
                       str_contains(strtolower($i['category'] ?? ''), $s);
            });
        }

        usort($items, fn($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));

        return array_values($items);
    }

    public function getByUserId(string $userId): array
    {
        $items = $this->firestore->collection($this->collection)->where('userId', '=', $userId);
        usort($items, fn($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));
        return array_values($items);
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->firestore->collection($this->collection)->doc($id)->update([
            'status' => $status,
            'updatedAt' => now()->toIso8601String(),
        ]);
    }

    public function updatePriority(string $id, string $priority): void
    {
        $this->firestore->collection($this->collection)->doc($id)->update([
            'priority' => $priority,
            'updatedAt' => now()->toIso8601String(),
        ]);
    }

    public function updateCategory(string $id, string $category): void
    {
        $this->firestore->collection($this->collection)->doc($id)->update([
            'category' => $category,
            'updatedAt' => now()->toIso8601String(),
        ]);
    }

    public function addInternalNote(string $id, string $note, string $author): void
    {
        $doc = $this->findById($id);
        if ($doc) {
            $notes = $doc['internalNotes'] ?? [];
            $notes[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'note' => $note,
                'author' => $author,
                'createdAt' => now()->toIso8601String(),
            ];
            $this->firestore->collection($this->collection)->doc($id)->update([
                'internalNotes' => $notes,
                'updatedAt' => now()->toIso8601String(),
            ]);
        }
    }

    public function delete(string $id): void
    {
        $this->firestore->collection($this->collection)->doc($id)->delete();
    }

    public function getStats(): array
    {
        $all = $this->firestore->collection($this->collection)->get();
        $total = count($all);
        $new = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'new'));
        $closed = count(array_filter($all, fn($i) => in_array(strtolower($i['status'] ?? ''), ['closed', 'resolved'])));
        $inProgress = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'in progress'));

        return [
            'total' => $total,
            'new' => $new,
            'closed' => $closed,
            'inProgress' => $inProgress,
        ];
    }
}

<?php

namespace App\Repositories\Postgres;

use App\Models\Feedback;
use App\Repositories\Contracts\FeedbackRepositoryInterface;

class PostgresFeedbackRepository implements FeedbackRepositoryInterface
{
    public function create(array $data): array
    {
        $payload = array_merge([
            'status' => 'New',
            'priority' => 'Medium',
            'category' => 'Other',
        ], [
            'telegram_user_id' => $data['userId'] ?? null,
            'telegram_id' => $data['telegramId'] ?? null,
            'user_name' => $data['userName'] ?? null,
            'username' => $data['username'] ?? null,
            'language' => $data['language'] ?? 'am',
            'type' => $data['type'] ?? 'text',
            'message' => $data['message'] ?? '',
            'attachment_url' => $data['attachmentUrl'] ?? null,
            'attachment_type' => $data['attachmentType'] ?? null,
            'file_name' => $data['fileName'] ?? null,
            'telegram_message_id' => $data['telegramMessageId'] ?? null,
        ]);

        $feedback = Feedback::create($payload);
        return $feedback->fresh(['replies', 'notes'])->toApiArray();
    }

    public function findById(string $id): ?array
    {
        $feedback = Feedback::with(['replies', 'notes'])->find($id);
        return $feedback ? $feedback->toApiArray() : null;
    }

    public function getAll(array $filters = []): array
    {
        $query = Feedback::with(['replies', 'notes']);

        if (!empty($filters['sort_by'])) {
            $dir = (!empty($filters['sort_order']) && strtolower($filters['sort_order']) === 'asc') ? 'asc' : 'desc';
            $col = $filters['sort_by'];
            if ($col === 'createdAt') $col = 'created_at';
            if ($col === 'userName' || $col === 'sender') $col = 'user_name';
            
            // Allow sorting by columns that exist
            $query->orderBy($col, $dir);
        } else {
            $query->chronological();
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        if (!empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (!empty($filters['search'])) {
            $s = strtolower($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(message) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(user_name) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(category) LIKE ?', ["%{$s}%"]);
            });
        }

        return $query->get()->map->toApiArray()->all();
    }

    public function getByUserId(string $userId): array
    {
        return Feedback::with(['replies', 'notes'])
            ->where('telegram_user_id', $userId)
            ->chronological()
            ->get()
            ->map->toApiArray()
            ->all();
    }

    public function updateStatus(string $id, string $status): void
    {
        Feedback::where('id', $id)->update(['status' => $status]);
    }

    public function updatePriority(string $id, string $priority): void
    {
        Feedback::where('id', $id)->update(['priority' => $priority]);
    }

    public function updateCategory(string $id, string $category): void
    {
        Feedback::where('id', $id)->update(['category' => $category]);
    }

    public function addInternalNote(string $id, string $note, string $author): void
    {
        $feedback = Feedback::find($id);
        if ($feedback) {
            $feedback->notes()->create([
                'note' => $note,
                'author' => $author,
            ]);
            $feedback->touch(); // update updated_at
        }
    }

    public function addReply(string $id, string $message, string $author, string $adminId = null): array
    {
        $feedback = Feedback::find($id);
        if (!$feedback) {
            throw new \Exception('Feedback not found');
        }

        $feedback->replies()->create([
            'message' => $message,
            'author' => $author,
            'admin_id' => $adminId,
        ]);
        
        $feedback->update(['status' => 'Resolved']);
        return $feedback->fresh(['replies', 'notes'])->toApiArray();
    }

    public function delete(string $id): void
    {
        Feedback::where('id', $id)->delete();
    }

    public function getStats(): array
    {
        $total = Feedback::count();
        $new = Feedback::where('status', 'New')->count();
        $closed = Feedback::whereIn('status', ['Closed', 'Resolved'])->count();
        $inProgress = Feedback::where('status', 'In Progress')->count();

        return [
            'total' => $total,
            'new' => $new,
            'closed' => $closed,
            'inProgress' => $inProgress,
        ];
    }
}

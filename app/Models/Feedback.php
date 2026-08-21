<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Feedback extends Model
{
    use HasUuids;

    protected $table = 'feedback';

    protected $fillable = [
        'telegram_user_id', 'telegram_id', 'user_name', 'username',
        'language', 'category', 'priority', 'status', 'message', 'type',
        'attachment_url', 'attachment_type', 'file_name', 'telegram_message_id',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function replies()
    {
        return $this->hasMany(FeedbackReply::class)->latest();
    }

    public function notes()
    {
        return $this->hasMany(FeedbackNote::class)->latest();
    }

    /**
     * Serialize to the API array format the frontend expects (camelCase).
     */
    public function toApiArray(): array
    {
        return [
            'id'                => $this->id,
            'telegramId'        => $this->telegram_id,
            'userId'            => (string) $this->telegram_user_id,
            'userName'          => $this->user_name,
            'username'          => $this->username,
            'language'          => $this->language,
            'category'          => $this->category,
            'priority'          => $this->priority,
            'status'            => $this->status,
            'message'           => $this->message,
            'type'              => $this->type,
            'attachmentUrl'     => $this->attachment_url,
            'attachmentType'    => $this->attachment_type,
            'fileName'          => $this->file_name,
            'telegramMessageId' => $this->telegram_message_id,
            'createdAt'         => $this->created_at?->toIso8601String(),
            'updatedAt'         => $this->updated_at?->toIso8601String(),
            'replies'           => $this->replies->map->toApiArray()->values()->all(),
            'internalNotes'     => $this->notes->map->toApiArray()->values()->all(),
        ];
    }
}

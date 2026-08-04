<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model {
    protected $fillable = [
        'telegram_id',
        'language',
        'first_name',
        'last_name',
        'username',
        'chat_id',
        'preferred_language',
        'active',
        'last_activity_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    public function toApiArray(): array
    {
        return [
            'id'                => (string) $this->id,
            'telegramId'        => $this->telegram_id,
            'chatId'            => $this->chat_id,
            'username'          => $this->username,
            'firstName'         => $this->first_name,
            'lastName'          => $this->last_name,
            'preferredLanguage' => $this->preferred_language,
            'language'          => $this->language,
            'active'            => $this->active,
            'lastActivity'      => $this->last_activity_at?->toIso8601String(),
            'joinedAt'          => $this->created_at?->toIso8601String(),
        ];
    }
}

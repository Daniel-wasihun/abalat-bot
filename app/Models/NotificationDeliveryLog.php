<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDeliveryLog extends Model
{
    protected $table = 'notification_delivery_logs';

    protected $fillable = [
        'notification_id', 'telegram_user_id', 'telegram_id', 'status', 'error_message', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(BotNotification::class, 'notification_id');
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function toApiArray(): array
    {
        return [
            'id'             => (string) $this->id,
            'notificationId' => $this->notification_id,
            'userId'         => (string) $this->telegram_user_id,
            'telegramId'     => $this->telegram_id,
            'status'         => $this->status,
            'error'          => $this->error_message,
            'sentAt'         => $this->sent_at?->toIso8601String(),
        ];
    }
}

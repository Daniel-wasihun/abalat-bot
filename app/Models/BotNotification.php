<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BotNotification extends Model
{
    use HasUuids;

    protected $table = 'bot_notifications';

    protected $fillable = [
        'title', 'message', 'sent_by', 'target_type', 'target_value',
        'total_recipients', 'sent_count', 'failed_count', 'status', 'scheduled_at',
    ];

    protected $casts = [
        'target_value' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function deliveryLogs()
    {
        return $this->hasMany(NotificationDeliveryLog::class, 'notification_id');
    }

    public function toApiArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'message'         => $this->message,
            'sentBy'          => $this->sent_by,
            'targetType'      => $this->target_type,
            'targetValue'     => $this->target_value,
            'totalRecipients' => $this->total_recipients,
            'sentCount'       => $this->sent_count,
            'failedCount'     => $this->failed_count,
            'status'          => $this->status,
            'scheduledAt'     => $this->scheduled_at?->toIso8601String(),
            'createdAt'       => $this->created_at?->toIso8601String(),
            'updatedAt'       => $this->updated_at?->toIso8601String(),
        ];
    }
}

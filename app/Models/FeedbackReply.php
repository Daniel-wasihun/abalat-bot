<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedbackReply extends Model
{
    use HasUuids;

    protected $table = 'feedback_replies';

    protected $fillable = [
        'feedback_id', 'author', 'admin_id', 'message',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function toApiArray(): array
    {
        return [
            'id'         => $this->id,
            'feedbackId' => $this->feedback_id,
            'author'     => $this->author,
            'adminId'    => $this->admin_id,
            'message'    => $this->message,
            'createdAt'  => $this->created_at?->toIso8601String(),
            'updatedAt'  => $this->updated_at?->toIso8601String(),
        ];
    }
}

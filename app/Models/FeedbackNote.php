<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedbackNote extends Model
{
    use HasUuids;

    protected $table = 'feedback_notes';

    protected $fillable = [
        'feedback_id', 'author', 'note',
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
            'note'       => $this->note,
            'createdAt'  => $this->created_at?->toIso8601String(),
            'updatedAt'  => $this->updated_at?->toIso8601String(),
        ];
    }
}

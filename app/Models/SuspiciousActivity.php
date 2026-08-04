<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivity extends Model {
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'activity_type',
        'request_data',
        'url',
        'method',
        'severity',
    ];

    protected $casts = [
        'request_data' => 'array',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportResult extends Model {
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'file_name',
        'total_rows',
        'processed_rows',
        'imported_count',
        'errors',
        'success_log',
        'user_id',
    ];

    protected $casts = [
        'errors' => 'array',
        'success_log' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}

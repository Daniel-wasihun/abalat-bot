<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralAttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'notes',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GeneralAttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

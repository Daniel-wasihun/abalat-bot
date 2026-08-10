<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenbetMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'senbet_memberships';

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'education_level',
        'emergency_name',
        'emergency_phone',
        'emergency_sub_city',
        'emergency_woreda',
        'emergency_house_number',
        'emergency_address',
        'registration_date',
        'senbet_class',
        'previous_participation',
        'previous_participation_document',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'registration_date' => 'date',
        'previous_participation' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

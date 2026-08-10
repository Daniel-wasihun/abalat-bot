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

    protected static function booted()
    {
        static::saved(function ($membership) {
            if ($membership->senbet_class) {
                // Find all active courses for this class
                $courses = Course::where('senbet_class', $membership->senbet_class)
                                ->where('is_active', true)
                                ->pluck('id');
                
                if ($courses->isNotEmpty()) {
                    // Sync without detaching to automatically enroll the student
                    $membership->user->enrolledCourses()->syncWithoutDetaching($courses);
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

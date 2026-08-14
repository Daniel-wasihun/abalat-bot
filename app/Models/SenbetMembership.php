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
            if (! $membership->senbet_class) return;

            // Find all active course offerings for this class
            $offerings = CourseOffering::where('senbet_class', $membership->senbet_class)
                ->where('is_active', true)
                ->get();

            foreach ($offerings as $offering) {
                // Enroll in the offering
                $membership->user->enrolledInOfferings()->syncWithoutDetaching([
                    $offering->id => ['status' => 'active', 'course_id' => $offering->course_id],
                ]);

                // Create a blank StudentResult placeholder
                StudentResult::firstOrCreate(
                    [
                        'student_id'          => $membership->user_id,
                        'course_offering_id'  => $offering->id,
                    ],
                    [
                        'recorded_by' => null,
                    ]
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Services\Academic\GradingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_offering_id',
        'scores',
        'total_score',
        'letter_grade',
        'remarks',
        'is_finalized',
        'recorded_by',
    ];

    protected $casts = [
        'scores'           => 'array',
        'total_score'      => 'float',
        'is_finalized'     => 'boolean',
    ];

    // ─── Boot: Auto-calculate & record history ────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (StudentResult $result) {
            // Auto-calculate total and letter grade before every save
            $grading = app(GradingService::class);
            $result->total_score  = $grading->calculateTotal($result->scores);
            $result->letter_grade = $grading->calculateLetterGrade($result->total_score);
        });

        static::updated(function (StudentResult $result) {
            // Record history after every successful update
            $dirty = $result->getChanges();
            $ignore = ['updated_at', 'created_at'];
            $changed = array_diff_key($dirty, array_flip($ignore));

            if (empty($changed)) return;

            $old = [];
            foreach (array_keys($changed) as $key) {
                $old[$key] = $result->getOriginal($key);
            }

            StudentResultHistory::create([
                'student_result_id' => $result->id,
                'changed_by'        => \Illuminate\Support\Facades\Auth::id() ?? $result->recorded_by,
                'old_values'        => $old,
                'new_values'        => $changed,
            ]);
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(StudentResultHistory::class)->orderByDesc('created_at');
    }
}

<?php

namespace App\Services\Academic;

/**
 * GradingService
 *
 * Handles score weight calculations and letter grade derivation.
 *
 * Default Ethiopian-style assessment weights:
 *   - Quiz / Continuous Assessment : 20 points
 *   - Midterm Exam                 : 30 points
 *   - Final Exam                   : 50 points
 *   - Total                        : 100 points
 *
 * Letter grades (Ethiopian secondary/university scale):
 *   A  = 90 – 100
 *   B  = 80 – 89
 *   C  = 70 – 79
 *   D  = 60 – 69
 *   F  = below 60
 */
class GradingService
{
    // Maximum possible raw score for each component
    public const QUIZ_CA_MAX      = 20;
    public const MIDTERM_MAX      = 30;
    public const FINAL_MAX        = 50;
    public const TOTAL_MAX        = 100;

    // Grade thresholds (lower bound inclusive)
    protected array $gradeScale = [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'F' => 0,
    ];

    /**
     * Calculate the total score as sum of all dynamic assessment scores.
     *
     * @param array|null $scores Key-value pairs of assessment_code => score
     * @return float|null
     */
    public function calculateTotal(?array $scores): ?float
    {
        if (empty($scores)) {
            return null;
        }

        $total = 0.0;
        foreach ($scores as $score) {
            if (is_numeric($score)) {
                $total += (float) $score;
            }
        }

        return round(min($total, self::TOTAL_MAX), 2);
    }

    /**
     * Derive a letter grade from a total score out of 100.
     * Returns 'NG' (Not Graded) if total is null.
     */
    public function calculateLetterGrade(?float $total): ?string
    {
        if ($total === null) {
            return null;
        }

        foreach ($this->gradeScale as $letter => $threshold) {
            if ($total >= $threshold) {
                return $letter;
            }
        }

        return 'F';
    }

    /**
     * Return the component metadata from configured AssessmentTypes (dynamic).
     * Kept for compatibility; prefer fetching AssessmentType::orderBy('order') directly.
     */
    public function componentInfo(): array
    {
        return \App\Models\AssessmentType::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn($a) => [
                'key'   => $a->code,
                'label' => $a->name,
                'max'   => (float) $a->max_score,
            ])
            ->toArray();
    }
}

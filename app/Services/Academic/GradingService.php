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
     * Calculate the total score as sum of the three components.
     * Each component is scored out of its own max, and they sum to 100.
     *
     * @param float|null $quizCa     Score out of 20
     * @param float|null $midterm    Score out of 30
     * @param float|null $finalExam  Score out of 50
     * @return float|null            Total out of 100, or null if all are null
     */
    public function calculateTotal(?float $quizCa, ?float $midterm, ?float $finalExam): ?float
    {
        if ($quizCa === null && $midterm === null && $finalExam === null) {
            return null;
        }

        $total = ($quizCa ?? 0) + ($midterm ?? 0) + ($finalExam ?? 0);

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
     * Validate that a raw score does not exceed the component maximum.
     */
    public function validateQuizCa(?float $score): bool
    {
        return $score === null || ($score >= 0 && $score <= self::QUIZ_CA_MAX);
    }

    public function validateMidterm(?float $score): bool
    {
        return $score === null || ($score >= 0 && $score <= self::MIDTERM_MAX);
    }

    public function validateFinalExam(?float $score): bool
    {
        return $score === null || ($score >= 0 && $score <= self::FINAL_MAX);
    }

    /**
     * Return the component metadata for use in the API response / frontend display.
     */
    public function componentInfo(): array
    {
        return [
            ['key' => 'quiz_ca_score',    'label' => 'Quiz / CA', 'max' => self::QUIZ_CA_MAX],
            ['key' => 'midterm_score',    'label' => 'Midterm',   'max' => self::MIDTERM_MAX],
            ['key' => 'final_exam_score', 'label' => 'Final Exam','max' => self::FINAL_MAX],
        ];
    }
}

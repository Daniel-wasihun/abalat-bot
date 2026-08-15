<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentType;

class AssessmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assessments = [
            ['code' => 'attendance_score', 'name' => 'Attendance & Participation', 'max_score' => 10, 'order' => 1],
            ['code' => 'assignment_score', 'name' => 'Assignments / Projects', 'max_score' => 20, 'order' => 2],
            ['code' => 'midterm_score', 'name' => 'Midterm Exam', 'max_score' => 30, 'order' => 3],
            ['code' => 'final_exam_score', 'name' => 'Final Exam', 'max_score' => 40, 'order' => 4],
        ];

        foreach ($assessments as $assessment) {
            AssessmentType::updateOrCreate(
                ['code' => $assessment['code']],
                [
                    'name' => $assessment['name'],
                    'max_score' => $assessment['max_score'],
                    'order' => $assessment['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

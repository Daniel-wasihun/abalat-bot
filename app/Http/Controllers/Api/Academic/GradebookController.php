<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AssessmentComponent;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradebookController extends Controller
{
    public function index($courseId)
    {
        $components = AssessmentComponent::where('course_id', $courseId)->get();
        return response()->json($components);
    }

    public function store(Request $request, $courseId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:1|max:100',
            'type' => 'required|in:exam,quiz,assignment,participation,other'
        ]);

        // Check if total percentage exceeds 100
        $currentTotal = AssessmentComponent::where('course_id', $courseId)->sum('percentage');
        if (($currentTotal + $request->percentage) > 100) {
            return response()->json(['message' => 'Total assessment percentage cannot exceed 100%'], 422);
        }

        $component = AssessmentComponent::create([
            'course_id' => $courseId,
            'name' => $request->name,
            'percentage' => $request->percentage,
            'type' => $request->type
        ]);

        return response()->json(['message' => 'Assessment component created successfully', 'component' => $component], 201);
    }

    public function destroy($courseId, $assessmentId)
    {
        AssessmentComponent::where('course_id', $courseId)->where('id', $assessmentId)->delete();
        return response()->json(['message' => 'Assessment component deleted successfully']);
    }

    public function getMarks($courseId)
    {
        $components = AssessmentComponent::where('course_id', $courseId)->pluck('id');
        $marks = StudentMark::whereIn('assessment_component_id', $components)->get();
        
        return response()->json($marks);
    }

    public function saveMarks(Request $request, $courseId)
    {
        $request->validate([
            'marks' => 'required|array',
            'marks.*.assessment_component_id' => 'required|exists:assessment_components,id',
            'marks.*.student_id' => 'required|exists:users,id',
            'marks.*.marks_obtained' => 'required|numeric|min:0'
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->marks as $markData) {
                StudentMark::updateOrCreate(
                    [
                        'assessment_component_id' => $markData['assessment_component_id'],
                        'student_id' => $markData['student_id']
                    ],
                    ['marks_obtained' => $markData['marks_obtained']]
                );
            }
        });

        return response()->json(['message' => 'Marks saved successfully']);
    }
}

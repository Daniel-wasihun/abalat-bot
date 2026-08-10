<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function assignTeachers(Request $request, $courseId)
    {
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:users,id'
        ]);

        $course = Course::findOrFail($courseId);
        $course->teachers()->sync($request->teacher_ids);

        return response()->json(['message' => 'Teachers assigned successfully']);
    }

    public function courseStudents($courseId)
    {
        $course = Course::findOrFail($courseId);
        return response()->json($course->students()->select('users.id', 'users.name', 'users.email')->get());
    }

    public function enrollStudent(Request $request, $courseId)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id'
        ]);

        $course = Course::findOrFail($courseId);
        $course->students()->syncWithoutDetaching([$request->student_id => ['status' => 'active']]);

        return response()->json(['message' => 'Student enrolled successfully']);
    }

    public function unenrollStudent(Request $request, $courseId)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id'
        ]);

        $course = Course::findOrFail($courseId);
        $course->students()->detach($request->student_id);

        return response()->json(['message' => 'Student unenrolled successfully']);
    }
}

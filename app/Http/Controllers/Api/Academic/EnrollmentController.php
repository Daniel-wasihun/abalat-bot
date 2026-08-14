<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\StudentResult;
use App\Models\User;
use App\Http\Requests\Academic\EnrollStudentRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * [LEGACY] Assign teachers to a course via the old flat course_teacher pivot.
     * Prefer TeacherAssignmentController::sync() for offering-scoped assignments.
     */
    public function assignTeachers(Request $request, $courseId)
    {
        $request->validate([
            'teacher_ids'   => 'required|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $course = Course::findOrFail($courseId);
        $course->teachers()->sync($request->teacher_ids);

        return response()->json(['message' => 'Teachers assigned successfully']);
    }

    // ─── Offering-Scoped Enrollment ───────────────────────────────────────────

    /**
     * Get all students enrolled in a specific offering.
     * Only the assigned teacher or admins can access.
     */
    public function offeringStudents(Request $request, int $offeringId)
    {
        $offering = CourseOffering::findOrFail($offeringId);
        $user     = $request->user();

        $this->authorizeAccess($user, $offering);

        $students = $offering->students()
            ->select('users.id', 'users.name', 'users.email')
            ->with('info:user_id,registration_id,gender,profile_picture,phone_number')
            ->get();

        return UserResource::collection($students);
    }

    /**
     * Enroll a student in an offering and create a blank result record.
     */
    public function enrollInOffering(EnrollStudentRequest $request, int $offeringId)
    {
        $validated = $request->validated();
        $offering = CourseOffering::findOrFail($offeringId);

        // Enroll in the offering
        $offering->students()->syncWithoutDetaching([
            $validated['student_id'] => [
                'status'    => 'active',
                'course_id' => $offering->course_id,
            ],
        ]);

        // Create blank result record
        StudentResult::firstOrCreate(
            ['student_id' => $validated['student_id'], 'course_offering_id' => $offeringId],
            ['recorded_by' => \Illuminate\Support\Facades\Auth::id()]
        );

        return response()->json(['message' => 'Student enrolled successfully']);
    }

    /**
     * Unenroll a student from an offering.
     */
    public function unenrollFromOffering(EnrollStudentRequest $request, int $offeringId)
    {
        $validated = $request->validated();
        $offering = CourseOffering::findOrFail($offeringId);
        $offering->students()->detach($validated['student_id']);

        return response()->json(['message' => 'Student unenrolled successfully']);
    }

    // ─── Legacy Course-Scoped Enrollment (kept for backward compat) ───────────

    public function courseStudents($courseId)
    {
        $course = Course::findOrFail($courseId);
        return UserResource::collection(
            $course->students()->select('users.id', 'users.name', 'users.email')->get()
        );
    }

    public function enrollStudent(Request $request, $courseId)
    {
        $request->validate(['student_id' => 'required|exists:users,id']);
        $course = Course::findOrFail($courseId);
        $course->students()->syncWithoutDetaching([$request->student_id => ['status' => 'active']]);
        return response()->json(['message' => 'Student enrolled successfully']);
    }

    public function unenrollStudent(Request $request, $courseId)
    {
        $request->validate(['student_id' => 'required|exists:users,id']);
        $course = Course::findOrFail($courseId);
        $course->students()->detach($request->student_id);
        return response()->json(['message' => 'Student unenrolled successfully']);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    protected function authorizeAccess($user, CourseOffering $offering): void
    {
        if ($user->hasPermission('academic_courses.manage')) return;

        $isAssigned = $offering->teacherAssignments()
            ->where('teacher_id', $user->id)
            ->exists();

        if (! $isAssigned) {
            abort(403, 'You are not assigned to this course offering.');
        }
    }
}


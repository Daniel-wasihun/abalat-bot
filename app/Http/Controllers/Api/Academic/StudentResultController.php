<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\StudentResult;
use App\Services\Academic\GradingService;
use App\Http\Requests\Academic\StudentResultRequest;
use App\Http\Resources\Academic\StudentResultResource;
use App\Http\Resources\Academic\CourseOfferingResource;
use Illuminate\Http\Request;

class StudentResultController extends Controller
{
    public function __construct(protected GradingService $grading) {}

    /**
     * Get all student results for an offering.
     * The requesting teacher must be assigned to this offering.
     */
    public function index(Request $request, int $offeringId)
    {
        $offering = CourseOffering::findOrFail($offeringId);
        $user     = $request->user();

        // Enforce teacher is assigned to this offering (admins bypass)
        $this->authorizeTeacher($user, $offering);

        $results = StudentResult::where('course_offering_id', $offeringId)
            ->with([
                'student:id,name,email',
                'student.info:user_id,profile_picture,registration_id,gender',
                'recordedBy:id,name',
            ])
            ->get();

        return response()->json([
            'offering'       => new CourseOfferingResource($offering->load('course:id,name,code')),
            'component_info' => $this->grading->componentInfo(),
            'results'        => StudentResultResource::collection($results),
        ]);
    }

    /**
     * Get a single student's result + edit history.
     */
    public function show(Request $request, int $offeringId, int $studentId)
    {
        $offering = CourseOffering::findOrFail($offeringId);
        $this->authorizeTeacher($request->user(), $offering);

        $result = StudentResult::where('course_offering_id', $offeringId)
            ->where('student_id', $studentId)
            ->with([
                'student:id,name,email',
                'student.info:user_id,profile_picture,registration_id,gender',
                'history.changedBy:id,name',
            ])
            ->firstOrFail();

        return new StudentResultResource($result);
    }

    /**
     * Update (or create) a student's result for an offering.
     * Auto-calculates total + letter grade via GradingService.
     */
    public function update(StudentResultRequest $request, int $offeringId, int $studentId)
    {
        $offering = CourseOffering::findOrFail($offeringId);
        $user     = $request->user();

        $this->authorizeTeacher($user, $offering);

        $validated = $request->validated();

        $result = StudentResult::where('course_offering_id', $offeringId)
            ->where('student_id', $studentId)
            ->firstOrCreate(
                ['course_offering_id' => $offeringId, 'student_id' => $studentId],
                ['recorded_by'        => $user->id]
            );

        // Block editing finalized results (unless admin)
        if ($result->is_finalized && ! $user->hasPermission('academic_courses.manage')) {
            return response()->json(['message' => 'This result has been finalized and cannot be edited.'], 403);
        }

        // Store change_reason temporarily so the history hook can pick it up
        $changeReason = $validated['change_reason'] ?? null;
        unset($validated['change_reason']);

        $result->fill($validated);
        $result->recorded_by = $user->id;

        // Manually trigger history with reason (booted() will create the history entry)
        // We use a custom listener after save
        $result->save();

        // Attach change_reason to the latest history entry if provided
        if ($changeReason && $result->history()->exists()) {
            $result->history()->latest()->first()?->update(['change_reason' => $changeReason]);
        }

        return StudentResultResource::success(
            $result->fresh(['student.info', 'history.changedBy']),
            'student_result_saved'
        );
    }

    /**
     * Finalize all results for an offering (admin only).
     * Once finalized, teachers cannot edit.
     */
    public function finalize(Request $request, int $offeringId)
    {
        CourseOffering::findOrFail($offeringId);

        StudentResult::where('course_offering_id', $offeringId)
            ->update(['is_finalized' => true]);

        return response()->json(['message' => 'All results finalized successfully']);
    }

    /**
     * Unfinalize (re-open) an offering's results (admin only).
     */
    public function unfinalize(Request $request, int $offeringId)
    {
        CourseOffering::findOrFail($offeringId);

        StudentResult::where('course_offering_id', $offeringId)
            ->update(['is_finalized' => false]);

        return response()->json(['message' => 'Results re-opened successfully']);
    }

    /**
     * Get the edit history for one student's result.
     */
    public function history(Request $request, int $offeringId, int $studentId)
    {
        $offering = CourseOffering::findOrFail($offeringId);
        $this->authorizeTeacher($request->user(), $offering);

        $result = StudentResult::where('course_offering_id', $offeringId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $history = $result->history()->with('changedBy:id,name')->get();

        return response()->json($history);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Student self-view: get the authenticated student's own result for an offering they're enrolled in.
     */
    public function myResult(Request $request, int $offeringId)
    {
        $user     = $request->user();
        $offering = CourseOffering::findOrFail($offeringId);

        // Confirm student is enrolled
        $enrolled = $offering->students()->where('users.id', $user->id)->exists();
        if (! $enrolled && ! $user->hasPermission('academic_courses.manage')) {
            abort(403, 'You are not enrolled in this course offering.');
        }

        $result = StudentResult::where('course_offering_id', $offeringId)
            ->where('student_id', $user->id)
            ->with(['student:id,name,email', 'recordedBy:id,name'])
            ->first();

        return response()->json([
            'offering'       => new CourseOfferingResource($offering->load('course:id,name,code')),
            'component_info' => $this->grading->componentInfo(),
            'result'         => $result ? new StudentResultResource($result) : null,
        ]);
    }

    /**
     * Ensure the requesting user is a teacher assigned to this offering,
     * or has admin-level manage permission.
     */
    protected function authorizeTeacher($user, CourseOffering $offering): void
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

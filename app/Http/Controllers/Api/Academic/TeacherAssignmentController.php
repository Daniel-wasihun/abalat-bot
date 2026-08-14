<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Http\Requests\Academic\TeacherAssignmentRequest;
use App\Http\Resources\Academic\TeacherAssignmentResource;
use App\Http\Resources\Academic\CourseOfferingResource;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    /**
     * List all teacher assignments for an offering.
     */
    public function index(int $offeringId)
    {
        $offering = CourseOffering::with(['course:id,name,code'])->findOrFail($offeringId);

        $assignments = TeacherAssignment::where('course_offering_id', $offeringId)
            ->with([
                'teacher:id,name,email',
                'teacher.info:user_id,profile_picture,registration_id',
                'assignedBy:id,name',
            ])
            ->get();

        return response()->json([
            'offering'    => new CourseOfferingResource($offering),
            'assignments' => TeacherAssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Assign one or more teachers to an offering.
     * Replaces existing assignments (sync).
     */
    public function sync(TeacherAssignmentRequest $request, int $offeringId)
    {
        CourseOffering::findOrFail($offeringId);

        $validated = $request->validated();

        $assignedBy = \Illuminate\Support\Facades\Auth::id();

        // Remove assignments for teachers NOT in the new list
        TeacherAssignment::where('course_offering_id', $offeringId)
            ->whereNotIn('teacher_id', $validated['teacher_ids'])
            ->delete();

        // Upsert for each teacher in the new list
        $result = [];
        foreach ($validated['teacher_ids'] as $teacherId) {
            $result[] = TeacherAssignment::firstOrCreate(
                [
                    'teacher_id'         => $teacherId,
                    'course_offering_id' => $offeringId,
                ],
                [
                    'assigned_by' => $assignedBy,
                ]
            );
        }

        return TeacherAssignmentResource::collection(collect($result))->additional([
            'message' => 'Teachers synced successfully',
        ]);
    }

    /**
     * Remove a single teacher from an offering.
     */
    public function destroy(int $offeringId, int $assignmentId)
    {
        $assignment = TeacherAssignment::where('course_offering_id', $offeringId)
            ->findOrFail($assignmentId);

        $assignment->delete();

        return response()->json(['message' => 'Teacher removed from offering successfully']);
    }

    /**
     * Admin view: list all assignments across all offerings (paginated).
     */
    public function all(Request $request)
    {
        $assignments = TeacherAssignment::with([
            'teacher:id,name,email',
            'courseOffering.course:id,name,code',
        ])
        ->when($request->teacher_id, fn($q) => $q->where('teacher_id', $request->teacher_id))
        ->paginate(50);

        return TeacherAssignmentResource::collection($assignments);
    }

    /**
     * Search for users who have the 'teacher' role (for assignment modal).
     * Only teacher-role users are returned — no students or other roles.
     */
    public function searchTeachers(Request $request)
    {
        $search = $request->input('search', '');

        $query = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'))
            ->where('is_active', true)
            ->with(['info:user_id,profile_picture,registration_id'])
            ->select('id', 'name', 'email');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("(\"name\"::jsonb)->>'en' ilike ?", ["%{$search}%"])
                  ->orWhereRaw("(\"name\"::jsonb)->>'am' ilike ?", ["%{$search}%"])
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $teachers = $query->orderByRaw("(\"name\"::jsonb)->>'en' asc")->limit(20)->get();

        return response()->json([
            'data' => $teachers->map(fn($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'email' => $t->email,
            ]),
        ]);
    }
}

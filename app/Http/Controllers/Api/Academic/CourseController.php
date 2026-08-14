<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\StudentResult;
use App\Http\Requests\Academic\CourseRequest;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\Academic\CourseOfferingResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * List all courses with their offerings and assigned teachers.
     * Admin view.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Course::with([
            'offerings.teachers:id,name',
            'offerings.academicYear:id,year',
        ]);

        if ($request->filled('senbet_class')) {
            $query->where(function ($q) use ($request) {
                $q->where('senbet_class', $request->senbet_class)
                  ->orWhereHas('offerings', fn($o) => $o->where('senbet_class', $request->senbet_class));
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if ($request->boolean('all')) {
            return CourseResource::collection($query->get());
        }

        return CourseResource::collection($query->paginate($perPage));
    }

    /**
     * Create a new course and auto-generate offerings if grade levels are specified.
     */
    public function store(CourseRequest $request)
    {
        $data = $request->validated();
        $gradeLevels     = $data['grade_levels'] ?? [];
        $academicYearId  = $data['academic_year_id'] ?? null;

        // Store the first grade level in the legacy senbet_class for backward compat
        if (! isset($data['senbet_class']) && ! empty($gradeLevels)) {
            $data['senbet_class'] = $gradeLevels[0];
        }

        // Remove non-course-table fields before creating
        unset($data['grade_levels'], $data['academic_year_id']);

        $course = DB::transaction(function () use ($data, $gradeLevels, $academicYearId) {
            $course = Course::create($data);

            // Auto-create one offering per grade level
            foreach ($gradeLevels as $gradeLevel) {
                CourseOffering::firstOrCreate([
                    'course_id'        => $course->id,
                    'senbet_class'     => $gradeLevel,
                    'semester'         => $data['semester'],
                    'academic_year_id' => $academicYearId,
                ], [
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }

            return $course;
        });

        return CourseResource::success(
            $course->load(['offerings.teachers:id,name', 'offerings.academicYear:id,year']),
            'course_created'
        );
    }

    /**
     * Show a single course with its offerings.
     */
    public function show($id)
    {
        $course = Course::with([
            'offerings.teachers:id,name',
            'offerings.academicYear:id,year',
        ])->findOrFail($id);

        return new CourseResource($course);
    }

    /**
     * Update a course.
     */
    public function update(CourseRequest $request, $id)
    {
        $course = Course::findOrFail($id);

        $course->update($request->validated());

        return CourseResource::success(
            $course->load(['offerings.teachers:id,name']),
            'course_updated'
        );
    }

    /**
     * Delete a course (cascades to offerings, assignments, results).
     */
    public function destroy($id)
    {
        Course::findOrFail($id)->delete();
        return response()->json(['message' => 'Course deleted successfully']);
    }

    /**
     * Offerings where the authenticated user is assigned as teacher.
     * Admins with academic_courses.manage see ALL active offerings.
     */
    public function myOfferings(Request $request)
    {
        $user = $request->user();

        $query = CourseOffering::where('is_active', true);

        // Non-admin: restrict to offerings this user is assigned to TEACH
        if (! $user->hasPermission('academic_courses.manage')) {
            $query->forTeacher($user->id);
        }

        $offerings = $query->with([
                'course:id,name,code,credit_hours,description',
                'academicYear:id,year',
            ])
            ->withCount('students')
            ->get();

        return CourseOfferingResource::collection($offerings);
    }

    /**
     * Offerings where the authenticated user is enrolled as a student.
     */
    public function myStudentCourses(Request $request)
    {
        $user = $request->user();

        $offerings = CourseOffering::where('is_active', true)
            ->forStudent($user->id)
            ->with([
                'course:id,name,code,credit_hours,description',
                'academicYear:id,year',
            ])
            ->withCount('students')
            ->get();

        return CourseOfferingResource::collection($offerings);
    }

    /**
     * Legacy endpoint: teacher's course list (backward compat for old MyClassesView).
     * @deprecated Use myOfferings() instead.
     */
    public function myClasses(Request $request)
    {
        $user = $request->user();
        $courses = $user->teachingCourses()->withCount('students')->get();
        return CourseResource::collection($courses);
    }
}


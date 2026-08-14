<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Http\Requests\Academic\CourseOfferingRequest;
use App\Http\Resources\Academic\CourseOfferingResource;
use Illuminate\Http\Request;

class CourseOfferingController extends Controller
{
    /**
     * List all offerings for a course.
     */
    public function index(Request $request, int $courseId)
    {
        $course = Course::findOrFail($courseId);

        $offerings = $course->offerings()
            ->with(['academicYear:id,year', 'teachers:id,name'])
            ->withCount('students')
            ->get();

        return CourseOfferingResource::collection($offerings);
    }

    /**
     * Create a new offering for a course (admin only).
     * If the same offering already exists (soft/hard), it can be reactivated.
     */
    public function store(CourseOfferingRequest $request, int $courseId)
    {
        Course::findOrFail($courseId);

        $validated = $request->validated();

        $offering = CourseOffering::firstOrCreate(
            [
                'course_id'        => $courseId,
                'senbet_class'     => $validated['senbet_class'],
                'semester'         => $validated['semester'],
                'academic_year_id' => $validated['academic_year_id'] ?? null,
            ],
            [
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        // If it existed but was inactive, reactivate it
        if (! $offering->wasRecentlyCreated && ! $offering->is_active) {
            $offering->update(['is_active' => true]);
        }

        return CourseOfferingResource::success(
            $offering->load(['academicYear:id,year', 'teachers:id,name']),
            'course_offering_created' // Using dynamic message key
        );
    }

    /**
     * Update an offering (toggle active status, change year).
     */
    public function update(CourseOfferingRequest $request, int $courseId, int $offeringId)
    {
        $offering = CourseOffering::where('course_id', $courseId)->findOrFail($offeringId);

        $validated = $request->validated();

        $offering->update($validated);

        return CourseOfferingResource::success(
            $offering->load(['academicYear:id,year', 'teachers:id,name']),
            'course_offering_updated'
        );
    }

    /**
     * Delete (deactivate) a course offering.
     */
    public function destroy(int $courseId, int $offeringId)
    {
        $offering = CourseOffering::where('course_id', $courseId)->findOrFail($offeringId);

        // Soft-delete by deactivating instead of hard-deleting to preserve enrollment records
        $offering->update(['is_active' => false]);

        return response()->json(['message' => 'Offering deactivated successfully']);
    }
}


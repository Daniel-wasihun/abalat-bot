<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query();

        if ($request->has('senbet_class')) {
            $query->where('senbet_class', $request->senbet_class);
        }
        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->with('teachers:id,name')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'senbet_class' => 'required|string',
            'semester' => 'required|in:1,2',
            'credit_hours' => 'required|integer|min:1',
            'prerequisites' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course = Course::create($validator->validated());

        return response()->json(['message' => 'Course created successfully', 'course' => $course], 201);
    }

    public function show($id)
    {
        $course = Course::with('teachers:id,name')->findOrFail($id);
        return response()->json($course);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:courses,code,' . $course->id,
            'senbet_class' => 'sometimes|required|string',
            'semester' => 'sometimes|required|in:1,2',
            'credit_hours' => 'sometimes|required|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'prerequisites' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course->update($validator->validated());

        return response()->json(['message' => 'Course updated successfully', 'course' => $course]);
    }

    public function destroy($id)
    {
        Course::findOrFail($id)->delete();
        return response()->json(['message' => 'Course deleted successfully']);
    }

    public function myClasses(Request $request)
    {
        $user = $request->user();
        $courses = $user->teachingCourses()->withCount('students')->get();
        return response()->json($courses);
    }
}

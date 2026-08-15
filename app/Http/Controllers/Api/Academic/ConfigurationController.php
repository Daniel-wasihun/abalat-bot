<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SenbetClass;
use App\Models\AssessmentType;
use Illuminate\Support\Str;

class ConfigurationController extends Controller
{
    // --- Classes Config ---

    public function getClasses()
    {
        return response()->json([
            'classes' => SenbetClass::orderBy('code')->get()
        ]);
    }

    public function storeClass(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:senbet_classes,code',
            'intake_capacity_per_section' => 'required|integer|min:1',
            'number_of_sections' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $class = SenbetClass::create($validated);
        return response()->json(['message' => 'Class created successfully', 'class' => $class]);
    }

    public function updateClass(Request $request, $id)
    {
        $class = SenbetClass::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:senbet_classes,code,' . $class->id,
            'intake_capacity_per_section' => 'required|integer|min:1',
            'number_of_sections' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $class->update($validated);
        return response()->json(['message' => 'Class updated successfully', 'class' => $class]);
    }

    public function destroyClass($id)
    {
        $class = SenbetClass::findOrFail($id);
        $class->delete();
        return response()->json(['message' => 'Class deleted successfully']);
    }

    // --- Assessments Config ---

    public function getAssessments()
    {
        return response()->json([
            'assessments' => AssessmentType::orderBy('order')->get()
        ]);
    }

    public function storeAssessment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assessment_types,code',
            'max_score' => 'required|numeric|min:0.1',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = Str::slug($validated['code'], '_');

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (!isset($validated['order'])) {
            $validated['order'] = AssessmentType::max('order') + 1;
        }

        $assessment = AssessmentType::create($validated);
        return response()->json(['message' => 'Assessment configuration created successfully', 'assessment' => $assessment]);
    }

    public function updateAssessment(Request $request, $id)
    {
        $assessment = AssessmentType::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assessment_types,code,' . $assessment->id,
            'max_score' => 'required|numeric|min:0.1',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = Str::slug($validated['code'], '_');

        $assessment->update($validated);
        return response()->json(['message' => 'Assessment configuration updated successfully', 'assessment' => $assessment]);
    }

    public function destroyAssessment($id)
    {
        $assessment = AssessmentType::findOrFail($id);
        $assessment->delete();
        return response()->json(['message' => 'Assessment configuration deleted successfully']);
    }
}

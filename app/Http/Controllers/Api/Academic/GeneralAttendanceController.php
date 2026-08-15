<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\GeneralAttendanceSession;
use App\Models\GeneralAttendanceRecord;
use App\Models\SenbetMembership;
use App\Models\SenbetClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralAttendanceController extends Controller
{
    /**
     * Get available classes (from config) and their sections.
     */
    public function getClasses()
    {
        $configuredClasses = SenbetClass::where('is_active', true)->orderBy('code')->get();

        $classes = [];
        foreach ($configuredClasses as $cls) {
            $sections = SenbetMembership::select('section')
                ->where('senbet_class', $cls->code)
                ->whereNotNull('section')
                ->distinct()
                ->orderBy('section')
                ->pluck('section')
                ->toArray();
            $classes[$cls->code] = [
                'label'    => $cls->name,
                'sections' => $sections,
            ];
        }

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get (or create) the general attendance session for a date/class/section.
     */
    public function getOrCreateSession(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'senbet_class' => 'required|string',
            'section'      => 'nullable|string',
        ]);

        $date = $request->date;
        $senbetClass = $request->senbet_class;
        $section = $request->section ?? null;

        // Find or create the session
        $session = GeneralAttendanceSession::firstOrCreate(
            [
                'date'         => $date,
                'senbet_class' => $senbetClass,
                'section'      => $section,
            ],
            [
                'recorded_by' => $request->user()->id,
            ]
        );

        // Fetch all students in this class/section
        $query = SenbetMembership::with('user:id,name')->where('senbet_class', $senbetClass);
        
        if ($section) {
            $query->where('section', $section);
        } else {
            $query->whereNull('section');
        }

        $memberships = $query->get();

        // Ensure records exist for all these students
        $session->load('records');
        $existingRecords = $session->records->keyBy('student_id');

        $studentsData = [];
        foreach ($memberships as $membership) {
            $studentId = $membership->user_id;
            
            $record = $existingRecords->get($studentId);
            $studentsData[] = [
                'id'              => $studentId,
                'name'            => $membership->user->name ?? '',
                'registration_id' => $membership->user->info->registration_id ?? null,
                'gender'          => $membership->user->info->gender ?? null,
                'status'          => $record ? $record->status : 'present', // default to present initially
                'notes'           => $record ? $record->notes : null,
            ];
        }

        return response()->json([
            'session'  => $session,
            'students' => collect($studentsData)->sortBy('name')->values()->all(),
            'is_saved' => $session->records->isNotEmpty(),
        ]);
    }

    /**
     * Save/upsert general attendance records for a session.
     */
    public function saveRecords(Request $request, $sessionId)
    {
        $request->validate([
            'records'              => 'required|array',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status'     => 'required|in:present,absent,permission',
            'records.*.notes'      => 'nullable|string|max:255',
        ]);

        $session = GeneralAttendanceSession::findOrFail($sessionId);

        DB::transaction(function () use ($request, $session) {
            foreach ($request->records as $recordData) {
                GeneralAttendanceRecord::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'student_id' => $recordData['student_id'],
                    ],
                    [
                        'status' => $recordData['status'],
                        'notes'  => $recordData['notes'] ?? null,
                    ]
                );
            }
            // Update recorded_by on edit
            $session->update(['recorded_by' => request()->user()->id]);
        });

        return response()->json([
            'message' => 'General attendance saved successfully',
            'session' => $session->fresh(),
        ]);
    }
}

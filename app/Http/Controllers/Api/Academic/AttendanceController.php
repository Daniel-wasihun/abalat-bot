<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Get all sessions for an offering.
     */
    public function index($offeringId)
    {
        $sessions = AttendanceSession::where('course_offering_id', $offeringId)
            ->with('teacher:id,name')
            ->withCount('records as total_students')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($sessions);
    }

    /**
     * Get (or create) the session for a given date, and return all student records.
     * This is the primary "load attendance for a date" endpoint.
     */
    public function getOrCreateByDate(Request $request, $offeringId)
    {
        $request->validate(['date' => 'required|date']);

        // Find or create the session for this offering+date
        $session = AttendanceSession::firstOrCreate(
            [
                'course_offering_id' => $offeringId,
                'date'               => $request->date,
            ],
            [
                'teacher_id' => $request->user()->id,
                'topic'      => null,
            ]
        );

        // Load existing records so the frontend can pre-populate the form
        $session->load('records:id,attendance_session_id,student_id,status,notes');

        return response()->json([
            'session'  => $session,
            'records'  => $session->records,
            'is_saved' => $session->records->isNotEmpty(),
        ]);
    }

    /**
     * Save (upsert) all attendance records for a session.
     * Creates or updates records — safe to call multiple times for edits.
     */
    public function saveRecords(Request $request, $offeringId, $sessionId)
    {
        $request->validate([
            'records'             => 'required|array',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status'    => 'required|in:present,absent,permission',
            'records.*.notes'     => 'nullable|string|max:255',
        ]);

        $session = AttendanceSession::where('id', $sessionId)
            ->where('course_offering_id', $offeringId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $session) {
            foreach ($request->records as $recordData) {
                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'student_id'            => $recordData['student_id'],
                    ],
                    [
                        'status' => $recordData['status'],
                        'notes'  => $recordData['notes'] ?? null,
                    ]
                );
            }
            // Update teacher on edit (in case session was created by system)
            $session->update(['teacher_id' => request()->user()->id]);
        });

        return response()->json([
            'message' => 'Attendance saved successfully',
            'session' => $session->fresh()->load('records'),
        ]);
    }

    /**
     * Show a single session with all records.
     */
    public function showSession($offeringId, $sessionId)
    {
        $session = AttendanceSession::where('id', $sessionId)
            ->where('course_offering_id', $offeringId)
            ->with('records.student:id,name,registration_id')
            ->firstOrFail();

        return response()->json($session);
    }
}

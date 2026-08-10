<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index($courseId)
    {
        $sessions = AttendanceSession::where('course_id', $courseId)
            ->with('teacher:id,name')
            ->withCount('records as total_students')
            ->orderBy('date', 'desc')
            ->get();
            
        return response()->json($sessions);
    }

    public function storeSession(Request $request, $courseId)
    {
        $request->validate([
            'date' => 'required|date',
            'topic' => 'nullable|string|max:255'
        ]);

        $session = AttendanceSession::create([
            'course_id' => $courseId,
            'teacher_id' => $request->user()->id,
            'date' => $request->date,
            'topic' => $request->topic
        ]);

        return response()->json(['message' => 'Attendance session created successfully', 'session' => $session], 201);
    }

    public function showSession($courseId, $sessionId)
    {
        $session = AttendanceSession::with('records.student:id,name')->findOrFail($sessionId);
        
        return response()->json($session);
    }

    public function saveRecords(Request $request, $courseId, $sessionId)
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late'
        ]);

        $session = AttendanceSession::findOrFail($sessionId);

        DB::transaction(function () use ($request, $session) {
            foreach ($request->records as $recordData) {
                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'student_id' => $recordData['student_id']
                    ],
                    ['status' => $recordData['status']]
                );
            }
        });

        return response()->json(['message' => 'Attendance records saved successfully']);
    }
}

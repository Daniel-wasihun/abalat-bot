<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditLogController extends Controller
{
    /**
     * List all audit logs, with optional filtering.
     */
    public function index(Request $request)
    {
        $query = Audit::with('user'); // user is the causer

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->query('auditable_type'));
        }

        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->query('auditable_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('event')) {
            $query->where('event', $request->query('event'));
        }

        // Sort latest first
        $query->orderBy('created_at', 'desc');

        $audits = $query->paginate($request->query('per_page', 20));

        // Format the output for the frontend
        $formatted = $audits->through(function ($audit) {
            $modelClass = class_basename($audit->auditable_type);
            
            return [
                'id' => $audit->id,
                'event' => ucfirst($audit->event),
                'model_type' => $modelClass,
                'model_id' => $audit->auditable_id,
                'causer_name' => $audit->user ? $audit->user->full_name_en : 'System',
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'created_at' => $audit->created_at->toIso8601String(),
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Rollback a specific model to the state it was in at the time of the given audit.
     */
    public function rollback($id)
    {
        $audit = Audit::findOrFail($id);
        
        // Cannot rollback 'created' events generically because it would mean deleting the record
        // which might violate foreign key constraints.
        if ($audit->event === 'created') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot rollback a creation event. Please delete the record manually if needed.'
            ], 400);
        }

        $modelClass = $audit->auditable_type;
        $model = $modelClass::find($audit->auditable_id);

        if (!$model) {
            return response()->json([
                'status' => 'error',
                'message' => 'The associated record no longer exists.'
            ], 404);
        }

        try {
            // transitionTo transitions the model attributes to how they were at this audit state
            $model->transitionTo($audit, true);
            $model->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Record successfully rolled back.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to rollback: ' . $e->getMessage()
            ], 500);
        }
    }
}

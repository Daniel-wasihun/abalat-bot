<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search']);
        $feedbacks = $this->feedbackService->getFilteredFeedback($filters);

        // Simple manual pagination simulation
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $paginated = array_slice($feedbacks, $offset, $perPage);
        $total = count($feedbacks);

        return response()->json([
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }

    public function show(string $id)
    {
        $feedback = $this->feedbackService->getFeedbackById($id);
        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found'], 404);
        }
        return response()->json($feedback);
    }

    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:New,Read,In Progress,Resolved,Closed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->feedbackService->updateStatus($id, $request->status);
        return response()->json(['message' => 'Feedback status updated successfully']);
    }

    public function updatePriority(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:Low,Medium,High,Critical',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->feedbackService->updatePriority($id, $request->priority);
        return response()->json(['message' => 'Feedback priority updated successfully']);
    }

    public function updateCategory(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|in:Bug,Suggestion,Complaint,Question,Other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->feedbackService->updateCategory($id, $request->category);
        return response()->json(['message' => 'Feedback category updated successfully']);
    }

    public function addNote(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->attributes->get('admin');
        $authorName = $admin['name'] ?? 'Admin';

        $this->feedbackService->addInternalNote($id, $request->note, $authorName);

        $feedback = $this->feedbackService->getFeedbackById($id);

        return response()->json([
            'message' => 'Internal note added successfully',
            'internalNotes' => $feedback['internalNotes'] ?? []
        ]);
    }

    public function destroy(string $id)
    {
        $this->feedbackService->deleteFeedback($id);
        return response()->json(['message' => 'Feedback deleted successfully']);
    }

    public function exportCsv(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search']);
        $csv = $this->feedbackService->exportCsv($filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search']);
        return $this->feedbackService->exportPdf($filters);
    }
}

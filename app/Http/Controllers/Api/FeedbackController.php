<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search', 'language']);
        $items   = $this->feedbackService->getFilteredFeedback($filters);

        $perPage  = max(1, (int) $request->get('per_page', 10));
        $page     = max(1, (int) $request->get('page', 1));
        $total    = count($items);
        $paginated = array_slice($items, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'data' => $paginated,
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
            ],
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
        $v = Validator::make($request->all(), [
            'status' => 'required|in:New,Read,In Progress,Resolved,Closed',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }
        $this->feedbackService->updateStatus($id, $request->status);
        return response()->json(['message' => 'Status updated successfully']);
    }

    public function updatePriority(Request $request, string $id)
    {
        $v = Validator::make($request->all(), [
            'priority' => 'required|in:Low,Medium,High,Critical',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }
        $this->feedbackService->updatePriority($id, $request->priority);
        return response()->json(['message' => 'Priority updated successfully']);
    }

    public function updateCategory(Request $request, string $id)
    {
        $v = Validator::make($request->all(), [
            'category' => 'required|in:Spiritual Education,Choir & Hymns,Liturgy & Service,Prayer Request,General Inquiry,Other',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }
        $this->feedbackService->updateCategory($id, $request->category);
        return response()->json(['message' => 'Category updated successfully']);
    }

    public function addNote(Request $request, string $id)
    {
        $v = Validator::make($request->all(), [
            'note' => 'required|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $admin      = $request->attributes->get('admin');
        $authorName = $admin['name'] ?? 'Admin';

        $this->feedbackService->addInternalNote($id, $request->note, $authorName);
        $feedback = $this->feedbackService->getFeedbackById($id);

        return response()->json([
            'message'       => 'Internal note added successfully',
            'internalNotes' => $feedback['internalNotes'] ?? [],
        ]);
    }

    public function reply(Request $request, string $id)
    {
        $v = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $admin      = $request->attributes->get('admin');
        $authorName = $admin['name'] ?? 'Admin';
        $adminId    = $admin['id']   ?? 'admin';

        try {
            $updatedFeedback = $this->feedbackService->replyToFeedback(
                $id,
                $request->message,
                $authorName,
                $adminId
            );
            return response()->json([
                'message'  => 'Reply sent to user via Telegram successfully',
                'feedback' => $updatedFeedback,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id)
    {
        $this->feedbackService->deleteFeedback($id);
        return response()->json(['message' => 'Feedback deleted successfully']);
    }

    public function exportCsv(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search', 'language']);
        $csv     = $this->feedbackService->exportCsv($filters);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'search', 'language']);
        return $this->feedbackService->exportPdf($filters);
    }
}

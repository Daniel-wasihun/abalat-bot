<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected UserRepositoryInterface $userRepo;
    protected FeedbackRepositoryInterface $feedbackRepo;
    protected NotificationRepositoryInterface $notificationRepo;
    protected TelegramBotService $botService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        FeedbackRepositoryInterface $feedbackRepo,
        NotificationRepositoryInterface $notificationRepo,
        TelegramBotService $botService
    ) {
        $this->userRepo = $userRepo;
        $this->feedbackRepo = $feedbackRepo;
        $this->notificationRepo = $notificationRepo;
        $this->botService = $botService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'active']);
        $users = $this->userRepo->getAll($filters);

        // Inject feedback count for each user
        foreach ($users as &$user) {
            $userFeedbacks = $this->feedbackRepo->getByUserId($user['id']);
            $user['feedbackCount'] = count($userFeedbacks);
        }

        // Simulating pagination
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $paginated = array_slice($users, $offset, $perPage);
        $total = count($users);

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
        $user = $this->userRepo->findById($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch feedback history
        $feedbackHistory = $this->feedbackRepo->getByUserId($id);

        // Fetch delivery logs for this user
        $logs = $this->notificationRepo->getLogsByNotificationId('*'); // Wildcard or filter manually
        $receivedLogs = array_filter($logs, fn($log) => ($log['userId'] ?? '') === $id);

        return response()->json([
            'user' => $user,
            'feedbacks' => $feedbackHistory,
            'notifications' => array_values($receivedLogs),
        ]);
    }

    public function toggleStatus(string $id)
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $newStatus = !($user['active'] ?? true);
        $this->userRepo->update($id, ['active' => $newStatus]);

        return response()->json([
            'message' => 'User status updated successfully',
            'active' => $newStatus
        ]);
    }

    public function sendDirectMessage(Request $request, string $id)
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $chatId = $user['chatId'] ?? null;
        if (!$chatId) {
            return response()->json(['message' => 'User chat ID not found'], 400);
        }

        $success = $this->botService->sendMessage($chatId, $request->message);

        if (!$success) {
            return response()->json(['message' => 'Failed to send message via Telegram Bot API'], 502);
        }

        return response()->json(['message' => 'Direct message sent successfully']);
    }
}

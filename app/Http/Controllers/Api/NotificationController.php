<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $notifications = $this->notificationService->getNotificationsList();
        return response()->json($notifications);
    }

    public function show(string $id)
    {
        $logs = $this->notificationService->getNotificationLogs($id);
        return response()->json([
            'logs' => $logs
        ]);
    }

    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'targetType' => 'required|in:all,active,selected,category',
            'targetValue' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estimate = $this->notificationService->estimateRecipients($request->targetType, $request->targetValue);
        return response()->json($estimate);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'targetType' => 'required|in:all,active,selected,category',
            'targetValue' => 'nullable',
            'scheduledAt' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->attributes->get('admin');
        $sentBy = $admin['name'] ?? 'Admin';

        $notification = $this->notificationService->createAndBroadcast($request->all(), $sentBy);

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification
        ]);
    }
}

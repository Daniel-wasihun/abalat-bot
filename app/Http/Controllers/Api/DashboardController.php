<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected UserRepositoryInterface $userRepo;
    protected FeedbackRepositoryInterface $feedbackRepo;
    protected NotificationRepositoryInterface $notificationRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        FeedbackRepositoryInterface $feedbackRepo,
        NotificationRepositoryInterface $notificationRepo
    ) {
        $this->userRepo = $userRepo;
        $this->feedbackRepo = $feedbackRepo;
        $this->notificationRepo = $notificationRepo;
    }

    public function index()
    {
        // 1. Stats and counters
        $totalUsers = $this->userRepo->getTotalUsersCount();
        $activeUsers = $this->userRepo->getActiveUsersCount();
        $feedbackStats = $this->feedbackRepo->getStats();
        $notificationStats = $this->notificationRepo->getStats();

        // 2. Recent activities
        $recentFeedback = array_slice($this->feedbackRepo->getAll(), 0, 5);
        $recentUsers = array_slice($this->userRepo->getAll(), 0, 5);

        $activities = [];
        foreach ($recentFeedback as $f) {
            $activities[] = [
                'id' => $f['id'],
                'type' => 'feedback',
                'title' => 'New Feedback Received',
                'description' => ($f['userName'] ?? 'Anonymous') . ' submitted feedback: "' . mb_strimwidth($f['message'] ?? '', 0, 50, '...') . '"',
                'time' => $f['createdAt'] ?? now()->toIso8601String(),
                'meta' => ['category' => $f['category'] ?? 'Other', 'status' => $f['status'] ?? 'New'],
            ];
        }
        foreach ($recentUsers as $u) {
            $activities[] = [
                'id' => $u['id'],
                'type' => 'user',
                'title' => 'New User Joined',
                'description' => ($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? '') . ' (@' . ($u['username'] ?? 'username') . ') joined the bot.',
                'time' => $u['joinedAt'] ?? now()->toIso8601String(),
                'meta' => ['lang' => $u['language'] ?? 'en'],
            ];
        }

        // Sort activities by time desc
        usort($activities, fn($a, $b) => strcmp($b['time'], $a['time']));
        $activities = array_slice($activities, 0, 8);

        // 3. Chart Data Generation (Mock/Real aggregate based on past 7 days)
        $chartData = $this->getChartData();

        return response()->json([
            'widgets' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'totalFeedback' => $feedbackStats['total'] ?? 0,
                'newFeedback' => $feedbackStats['new'] ?? 0,
                'closedFeedback' => $feedbackStats['closed'] ?? 0,
                'broadcastsSent' => $notificationStats['totalBroadcasts'] ?? 0,
                'successfulDeliveries' => $notificationStats['totalSent'] ?? 0,
                'failedDeliveries' => $notificationStats['totalFailed'] ?? 0,
            ],
            'recentActivity' => $activities,
            'charts' => $chartData,
        ]);
    }

    protected function getChartData(): array
    {
        $days = [];
        $feedbackCounts = [];
        $userCounts = [];

        // Generate past 7 days dates
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[] = now()->subDays($i)->format('M d');
            
            // Feedbacks count for this day
            $feedbacks = $this->feedbackRepo->getAll();
            $feedbackCounts[] = count(array_filter($feedbacks, function($f) use ($date) {
                return str_starts_with($f['createdAt'] ?? '', $date);
            }));

            // Users count for this day
            $users = $this->userRepo->getAll();
            $userCounts[] = count(array_filter($users, function($u) use ($date) {
                return str_starts_with($u['joinedAt'] ?? '', $date);
            }));
        }

        // Broadcast stats
        $notifications = $this->notificationRepo->getAll();
        $broadcastLabels = [];
        $broadcastSent = [];
        $broadcastFailed = [];

        foreach (array_slice($notifications, 0, 5) as $n) {
            $broadcastLabels[] = mb_strimwidth($n['title'] ?? 'Broadcast', 0, 15, '...');
            $broadcastSent[] = $n['sentCount'] ?? 0;
            $broadcastFailed[] = $n['failedCount'] ?? 0;
        }

        return [
            'feedbackOverTime' => [
                'labels' => $days,
                'datasets' => [
                    [
                        'label' => 'Feedback Volume',
                        'data' => $feedbackCounts,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    ]
                ]
            ],
            'userGrowth' => [
                'labels' => $days,
                'datasets' => [
                    [
                        'label' => 'New Subscribers',
                        'data' => $userCounts,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    ]
                ]
            ],
            'broadcastStats' => [
                'labels' => !empty($broadcastLabels) ? $broadcastLabels : ['No Broadcasts'],
                'datasets' => [
                    [
                        'label' => 'Delivered',
                        'data' => !empty($broadcastSent) ? $broadcastSent : [0],
                        'backgroundColor' => '#10b981',
                    ],
                    [
                        'label' => 'Failed',
                        'data' => !empty($broadcastFailed) ? $broadcastFailed : [0],
                        'backgroundColor' => '#ef4444',
                    ]
                ]
            ]
        ];
    }
}

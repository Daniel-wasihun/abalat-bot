<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface         $userRepo,
        protected FeedbackRepositoryInterface     $feedbackRepo,
        protected NotificationRepositoryInterface $notificationRepo
    ) {}

    public function index()
    {
        $data = Cache::remember('dashboard_data', 90, function () {
            $allFeedbacks = $this->feedbackRepo->getAll();
            $allUsers     = $this->userRepo->getAll();

            /* ── Widgets ──────────────────────────────────────── */
            $totalUsers  = count($allUsers);
            $activeUsers = count(array_filter($allUsers, fn($u) => ($u['active'] ?? true) === true));

            $totalFb      = count($allFeedbacks);
            $newFb        = count(array_filter($allFeedbacks, fn($f) => strtolower($f['status'] ?? '') === 'new'));
            $readFb       = count(array_filter($allFeedbacks, fn($f) => strtolower($f['status'] ?? '') === 'read'));
            $inProgressFb = count(array_filter($allFeedbacks, fn($f) => strtolower($f['status'] ?? '') === 'in progress'));
            $resolvedFb   = count(array_filter($allFeedbacks, fn($f) => strtolower($f['status'] ?? '') === 'resolved'));
            $closedFb     = count(array_filter($allFeedbacks, fn($f) => strtolower($f['status'] ?? '') === 'closed'));
            $repliedFb    = $resolvedFb; // resolved means replied
            $unreadFb     = $newFb;

            // Language distribution
            $langCounts = ['am' => 0, 'en' => 0, 'om' => 0];
            foreach ($allUsers as $u) {
                $lang = $u['preferredLanguage'] ?? $u['language'] ?? 'am';
                if (isset($langCounts[$lang])) {
                    $langCounts[$lang]++;
                }
            }

            $notificationStats = $this->notificationRepo->getStats();

            /* ── Recent Activity ──────────────────────────────── */
            $recentFeedback = array_slice($allFeedbacks, 0, 5);
            $recentUsers    = array_slice($allUsers, 0, 5);

            $activities = [];
            foreach ($recentFeedback as $f) {
                $activities[] = [
                    'id'          => $f['id'],
                    'type'        => 'feedback',
                    'title'       => 'New Feedback Received',
                    'description' => ($f['userName'] ?? 'Anonymous')
                        . ' — "' . mb_strimwidth($f['message'] ?? '', 0, 60, '…') . '"',
                    'time'        => $f['createdAt'] ?? now()->toIso8601String(),
                    'meta'        => [
                        'category' => $f['category'] ?? 'Other',
                        'status'   => $f['status']   ?? 'New',
                        'lang'     => $f['language']  ?? 'am',
                    ],
                ];
            }
            foreach ($recentUsers as $u) {
                $activities[] = [
                    'id'          => $u['id'],
                    'type'        => 'user',
                    'title'       => 'New Subscriber Joined',
                    'description' => trim(($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? ''))
                        . ' (@' . ($u['username'] ?? 'N/A') . ')',
                    'time'        => $u['joinedAt'] ?? now()->toIso8601String(),
                    'meta'        => [
                        'lang' => $u['preferredLanguage'] ?? $u['language'] ?? 'am',
                    ],
                ];
            }

            usort($activities, fn($a, $b) => strcmp($b['time'], $a['time']));
            $activities = array_slice($activities, 0, 10);

            /* ── Chart Data ───────────────────────────────────── */
            $chartData = $this->getChartData($allFeedbacks, $allUsers);

            return [
                'widgets' => [
                    'totalUsers'           => $totalUsers,
                    'activeUsers'          => $activeUsers,
                    'totalFeedback'        => $totalFb,
                    'newFeedback'          => $newFb,
                    'unreadFeedback'       => $unreadFb,
                    'readFeedback'         => $readFb,
                    'inProgressFeedback'   => $inProgressFb,
                    'resolvedFeedback'     => $resolvedFb,
                    'closedFeedback'       => $closedFb,
                    'repliedFeedback'      => $repliedFb,
                    'broadcastsSent'       => $notificationStats['totalBroadcasts']   ?? 0,
                    'successfulDeliveries' => $notificationStats['totalSent']         ?? 0,
                    'failedDeliveries'     => $notificationStats['totalFailed']       ?? 0,
                    'languageDistribution' => $langCounts,
                ],
                'recentActivity' => $activities,
                'charts'         => $chartData,
            ];
        });

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    protected function getChartData(array $allFeedbacks, array $allUsers): array
    {
        $days           = [];
        $feedbackCounts = [];
        $userCounts     = [];

        for ($i = 6; $i >= 0; $i--) {
            $date           = now()->subDays($i)->format('Y-m-d');
            $days[]         = now()->subDays($i)->format('M d');
            $feedbackCounts[] = count(array_filter($allFeedbacks, fn($f) => str_starts_with($f['createdAt'] ?? '', $date)));
            $userCounts[]     = count(array_filter($allUsers,     fn($u) => str_starts_with($u['joinedAt']   ?? '', $date)));
        }

        $notifications   = $this->notificationRepo->getAll();
        $broadcastLabels = [];
        $broadcastSent   = [];
        $broadcastFailed = [];

        foreach (array_slice($notifications, 0, 5) as $n) {
            $broadcastLabels[] = mb_strimwidth($n['title'] ?? 'Broadcast', 0, 15, '…');
            $broadcastSent[]   = $n['sentCount']   ?? 0;
            $broadcastFailed[] = $n['failedCount']  ?? 0;
        }

        return [
            'feedbackOverTime' => [
                'labels'   => $days,
                'datasets' => [[
                    'label'           => 'Feedback Volume',
                    'data'            => $feedbackCounts,
                    'borderColor'     => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.08)',
                ]],
            ],
            'userGrowth' => [
                'labels'   => $days,
                'datasets' => [[
                    'label'           => 'New Subscribers',
                    'data'            => $userCounts,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                ]],
            ],
            'broadcastStats' => [
                'labels'   => !empty($broadcastLabels) ? $broadcastLabels : ['No Broadcasts'],
                'datasets' => [
                    [
                        'label'           => 'Delivered',
                        'data'            => !empty($broadcastSent)   ? $broadcastSent   : [0],
                        'backgroundColor' => '#10b981',
                    ],
                    [
                        'label'           => 'Failed',
                        'data'            => !empty($broadcastFailed) ? $broadcastFailed : [0],
                        'backgroundColor' => '#ef4444',
                    ],
                ],
            ],
        ];
    }
}

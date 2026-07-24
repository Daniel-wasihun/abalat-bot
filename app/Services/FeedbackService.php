<?php

namespace App\Services;

use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\FirestoreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class FeedbackService
{
    public function __construct(
        protected FeedbackRepositoryInterface $feedbackRepo,
        protected UserRepositoryInterface     $userRepo,
        protected TelegramBotService          $botService,
        protected FirestoreService            $firestoreService
    ) {}

    /**
     * Send an admin reply to a user via Telegram and persist it to:
     *  1. The feedback document's `replies` array (for the dashboard UI)
     *  2. The `feedbackReplies` Firestore collection (for spec compliance)
     */
    public function replyToFeedback(string $id, string $messageText, string $authorName, string $adminId = 'admin'): array
    {
        $feedback = $this->feedbackRepo->findById($id);
        if (!$feedback) {
            throw new \Exception('Feedback not found');
        }

        // Resolve the user
        $user = null;
        if (!empty($feedback['userId'])) {
            $user = $this->userRepo->findById($feedback['userId']);
        }
        if (!$user && !empty($feedback['telegramId'])) {
            $user = $this->userRepo->findByTelegramId($feedback['telegramId']);
        }

        $chatId = $user['chatId'] ?? $feedback['telegramId'] ?? null;
        if (!$chatId) {
            throw new \Exception('User Telegram Chat ID not found. Cannot deliver reply.');
        }

        // Detect user language for a polite reply header
        $userLang   = $user['preferredLanguage'] ?? $user['language'] ?? 'am';
        $headers    = [
            'am' => '💬 የሰንበት ትምህርት ቤቱ ምላሽ',
            'om' => '💬 Deebii Mana Barumsa Dilbataa',
            'en' => '💬 Sunday School Response',
        ];
        $header = $headers[$userLang] ?? $headers['am'];

        $footers    = [
            'am' => 'ደቂቀ ብርሃን ሰንበት ትምህርት ቤት 🙏',
            'om' => 'M.B.D. Daqiiqaa Birhaan 🙏',
            'en' => 'Dekiqen Birhan Sunday School 🙏',
        ];
        $footer = $footers[$userLang] ?? $footers['am'];

        $telegramMessageId = isset($feedback['telegramMessageId']) ? (int) $feedback['telegramMessageId'] : null;
        $formattedMsg = "{$header}\n" .
            "————————————————————\n\n" .
            "{$messageText}\n\n" .
            "————————————————————\n" .
            "{$footer}";

        $sent = $this->botService->sendMessage($chatId, $formattedMsg, null, $telegramMessageId);
        if (!$sent) {
            throw new \Exception('Failed to send message via Telegram Bot API. Please check the bot token and user chat ID.');
        }

        // ── Build reply record ────────────────────────────────
        $replyId  = (string) Str::uuid();
        $replyDoc = [
            'id'         => $replyId,
            'feedbackId' => $id,
            'adminId'    => $adminId,
            'author'     => $authorName,
            'message'    => $messageText,
            'createdAt'  => now()->toIso8601String(),
        ];

        // ── 1. Update feedback document's replies array ───────
        $existing = $this->feedbackRepo->findById($id);
        $replies  = $existing['replies'] ?? [];
        $replies[] = $replyDoc;

        $this->firestoreService
            ->collection('feedback')
            ->doc($id)
            ->update([
                'replies'   => $replies,
                'status'    => 'Resolved',
                'updatedAt' => now()->toIso8601String(),
            ]);

        // ── 2. Write to separate feedbackReplies collection ───
        $this->firestoreService
            ->collection('feedbackReplies')
            ->add($replyDoc);

        return $this->feedbackRepo->findById($id);
    }

    public function getFilteredFeedback(array $filters = []): array
    {
        return $this->feedbackRepo->getAll($filters);
    }

    public function getFeedbackById(string $id): ?array
    {
        return $this->feedbackRepo->findById($id);
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->feedbackRepo->updateStatus($id, $status);
    }

    public function updatePriority(string $id, string $priority): void
    {
        $this->feedbackRepo->updatePriority($id, $priority);
    }

    public function updateCategory(string $id, string $category): void
    {
        $this->feedbackRepo->updateCategory($id, $category);
    }

    public function addInternalNote(string $id, string $note, string $author): void
    {
        $this->feedbackRepo->addInternalNote($id, $note, $author);
    }

    public function deleteFeedback(string $id): void
    {
        $this->feedbackRepo->delete($id);
    }

    public function exportCsv(array $filters = []): string
    {
        $items  = $this->getFilteredFeedback($filters);
        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['ID', 'User Name', 'Telegram ID', 'Language', 'Category', 'Priority', 'Status', 'Type', 'Message', 'Created At']);

        foreach ($items as $item) {
            fputcsv($output, [
                $item['id']          ?? '',
                $item['userName']    ?? '',
                $item['telegramId']  ?? '',
                $item['language']    ?? '',
                $item['category']    ?? '',
                $item['priority']    ?? '',
                $item['status']      ?? '',
                $item['type']        ?? '',
                $item['message']     ?? '',
                $item['createdAt']   ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function exportPdf(array $filters = []): \Illuminate\Http\Response
    {
        $items = $this->getFilteredFeedback($filters);
        $pdf   = Pdf::loadView('pdf.feedback_report', [
            'feedbacks'   => $items,
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
        return $pdf->download('feedback_report_' . date('Y-m-d') . '.pdf');
    }
}

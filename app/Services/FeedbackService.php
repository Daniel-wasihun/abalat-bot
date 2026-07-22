<?php

namespace App\Services;

use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;

class FeedbackService
{
    protected FeedbackRepositoryInterface $feedbackRepo;
    protected UserRepositoryInterface $userRepo;

    public function __construct(
        FeedbackRepositoryInterface $feedbackRepo,
        UserRepositoryInterface $userRepo
    ) {
        $this->feedbackRepo = $feedbackRepo;
        $this->userRepo = $userRepo;
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
        $items = $this->getFilteredFeedback($filters);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'User Name', 'Telegram ID', 'Category', 'Priority', 'Status', 'Type', 'Message', 'Created At']);

        foreach ($items as $item) {
            fputcsv($output, [
                $item['id'] ?? '',
                $item['userName'] ?? '',
                $item['telegramId'] ?? '',
                $item['category'] ?? '',
                $item['priority'] ?? '',
                $item['status'] ?? '',
                $item['type'] ?? '',
                $item['message'] ?? '',
                $item['createdAt'] ?? '',
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
        $pdf = Pdf::loadView('pdf.feedback_report', ['feedbacks' => $items, 'generatedAt' => now()->toDayDateTimeString()]);
        return $pdf->download('feedback_report_' . date('Y-m-d') . '.pdf');
    }
}

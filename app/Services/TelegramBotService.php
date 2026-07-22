<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected UserRepositoryInterface $userRepo;
    protected FeedbackRepositoryInterface $feedbackRepo;
    protected SettingRepositoryInterface $settingRepo;
    protected string $botToken;

    public function __construct(
        UserRepositoryInterface $userRepo,
        FeedbackRepositoryInterface $feedbackRepo,
        SettingRepositoryInterface $settingRepo
    ) {
        $this->userRepo = $userRepo;
        $this->feedbackRepo = $feedbackRepo;
        $this->settingRepo = $settingRepo;
        $this->botToken = env('TELEGRAM_BOT_TOKEN', '');
    }

    public function getBotToken(): string
    {
        $customToken = $this->settingRepo->get('bot_token');
        return !empty($customToken) ? $customToken : $this->botToken;
    }

    public function handleWebhookUpdate(array $update): void
    {
        Log::info('Telegram Webhook Payload:', $update);

        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        if (!isset($update['message'])) {
            return;
        }

        $message = $update['message'];
        $from = $message['from'] ?? null;
        $chat = $message['chat'] ?? null;

        if (!$from || !$chat) {
            return;
        }

        $telegramId = (string) $from['id'];
        $chatId = (string) $chat['id'];

        // Save or update Telegram user
        $user = $this->userRepo->createOrUpdateTelegramUser([
            'telegramId' => $telegramId,
            'chatId' => $chatId,
            'username' => $from['username'] ?? '',
            'firstName' => $from['first_name'] ?? '',
            'lastName' => $from['last_name'] ?? '',
            'language' => $from['language_code'] ?? 'en',
        ]);

        $text = trim($message['text'] ?? '');
        $userStateKey = "telegram_state_{$telegramId}";
        $currentState = Cache::get($userStateKey);

        // Standard command handling
        if ($text === '/start') {
            Cache::forget($userStateKey);
            $this->sendWelcomeMessage($chatId, $user['firstName'] ?? 'User');
            return;
        }

        if ($text === 'ℹ️ Help' || $text === '/help') {
            Cache::forget($userStateKey);
            $this->sendHelpMessage($chatId);
            return;
        }

        if ($text === '📋 My Feedback' || $text === '/myfeedback') {
            Cache::forget($userStateKey);
            $this->sendUserFeedbackList($chatId, $user['id']);
            return;
        }

        if ($text === '📝 Send Feedback' || $text === '/feedback') {
            Cache::put($userStateKey, 'awaiting_category', 3600);
            $this->sendCategorySelectionPrompt($chatId);
            return;
        }

        // Check if user is in feedback submission flow
        if ($currentState && str_starts_with($currentState, 'awaiting_content:')) {
            $category = str_replace('awaiting_content:', '', $currentState);
            $this->processFeedbackSubmission($chatId, $user, $message, $category);
            Cache::forget($userStateKey);
            return;
        }

        if ($currentState === 'awaiting_category') {
            // User typed text instead of pressing inline button
            $category = $this->normalizeCategory($text);
            Cache::put($userStateKey, "awaiting_content:{$category}", 3600);
            $this->sendMessage($chatId, "Category set to *{$category}*.\n\nPlease type your feedback message now or attach an image/document/voice note.");
            return;
        }

        // Fallback message
        $this->sendMessage(
            $chatId,
            "I didn't quite get that. Please select an option from the menu below or send /start.",
            $this->getMainMenuKeyboard()
        );
    }

    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $id = $callbackQuery['id'];
        $from = $callbackQuery['from'];
        $message = $callbackQuery['message'];
        $chatId = (string) $message['chat']['id'];
        $telegramId = (string) $from['id'];
        $data = $callbackQuery['data'] ?? '';

        $this->answerCallbackQuery($id);

        if (str_starts_with($data, 'cat:')) {
            $category = str_replace('cat:', '', $data);
            Cache::put("telegram_state_{$telegramId}", "awaiting_content:{$category}", 3600);
            $this->sendMessage($chatId, "Category selected: *{$category}*\n\nPlease type your feedback message now or send an image, document, or voice message.");
        }
    }

    protected function sendWelcomeMessage(string $chatId, string $firstName): void
    {
        $customWelcome = $this->settingRepo->get('welcome_message');
        $msg = $customWelcome ?: "Hello *{$firstName}*! 👋\n\nWelcome to the Feedback & Support Bot. We value your thoughts, bug reports, and suggestions.\n\nUse the menu below to navigate:";
        
        $this->sendMessage($chatId, $msg, $this->getMainMenuKeyboard());
    }

    protected function sendHelpMessage(string $chatId): void
    {
        $msg = "ℹ️ *Help & Usage Guide*\n\n" .
               "• Press *📝 Send Feedback* to share your thoughts, report bugs, or request features.\n" .
               "• Press *📋 My Feedback* to view your previously submitted feedback and their status.\n\n" .
               "You can attach photos, documents, or voice notes when submitting feedback.";

        $this->sendMessage($chatId, $msg, $this->getMainMenuKeyboard());
    }

    protected function sendCategorySelectionPrompt(string $chatId): void
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🐛 Bug Report', 'callback_data' => 'cat:Bug'],
                    ['text' => '💡 Suggestion', 'callback_data' => 'cat:Suggestion'],
                ],
                [
                    ['text' => '⚠️ Complaint', 'callback_data' => 'cat:Complaint'],
                    ['text' => '❓ Question', 'callback_data' => 'cat:Question'],
                ],
                [
                    ['text' => '📝 Other', 'callback_data' => 'cat:Other'],
                ]
            ]
        ];

        $this->sendMessage($chatId, "Please select the category for your feedback:", $keyboard);
    }

    protected function sendUserFeedbackList(string $chatId, string $userId): void
    {
        $feedbacks = $this->feedbackRepo->getByUserId($userId);

        if (empty($feedbacks)) {
            $this->sendMessage($chatId, "You haven't submitted any feedback yet. Click *📝 Send Feedback* to get started!");
            return;
        }

        $lines = ["📋 *Your Recent Feedback List:*\n"];
        foreach (array_slice($feedbacks, 0, 5) as $idx => $f) {
            $statusEmoji = match(strtolower($f['status'] ?? '')) {
                'new' => '🟡',
                'in progress' => '🔵',
                'resolved', 'closed' => '🟢',
                default => '⚪',
            };
            $msgSnippet = mb_strimwidth($f['message'] ?? 'No text', 0, 40, '...');
            $date = date('Y-m-d H:i', strtotime($f['createdAt'] ?? 'now'));
            $lines[] = ($idx + 1) . ". {$statusEmoji} [{$f['category']}] *{$f['status']}*\n   \"{$msgSnippet}\"\n   _Submitted on {$date}_\n";
        }

        $this->sendMessage($chatId, implode("\n", $lines), $this->getMainMenuKeyboard());
    }

    protected function processFeedbackSubmission(string $chatId, array $user, array $message, string $category): void
    {
        $type = 'text';
        $content = $message['text'] ?? $message['caption'] ?? '';
        $attachmentUrl = null;

        if (isset($message['photo'])) {
            $type = 'image';
            $photo = end($message['photo']);
            $attachmentUrl = $this->getFileUrl($photo['file_id']);
            if (empty($content)) {
                $content = '[Image Attachment]';
            }
        } elseif (isset($message['document'])) {
            $type = 'document';
            $doc = $message['document'];
            $attachmentUrl = $this->getFileUrl($doc['file_id']);
            if (empty($content)) {
                $content = '[Document Attachment: ' . ($doc['file_name'] ?? 'file') . ']';
            }
        } elseif (isset($message['voice'])) {
            $type = 'voice';
            $voice = $message['voice'];
            $attachmentUrl = $this->getFileUrl($voice['file_id']);
            if (empty($content)) {
                $content = '[Voice Message: ' . ($voice['duration'] ?? 0) . 's]';
            }
        }

        $this->feedbackRepo->create([
            'userId' => $user['id'],
            'telegramId' => $user['telegramId'],
            'userName' => ($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''),
            'username' => $user['username'] ?? '',
            'type' => $type,
            'message' => $content,
            'attachmentUrl' => $attachmentUrl,
            'category' => $category,
            'priority' => 'Medium',
            'status' => 'New',
        ]);

        $this->sendMessage(
            $chatId,
            "Thank you! Your feedback has been received. Our team will review it shortly. 🙏",
            $this->getMainMenuKeyboard()
        );
    }

    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        $token = $this->getBotToken();
        if (empty($token)) {
            Log::error('Telegram Bot Token is missing.');
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getFileUrl(string $fileId): string
    {
        $token = $this->getBotToken();
        try {
            $res = Http::get("https://api.telegram.org/bot{$token}/getFile?file_id={$fileId}");
            if ($res->successful() && isset($res->json()['result']['file_path'])) {
                return "https://api.telegram.org/file/bot{$token}/" . $res->json()['result']['file_path'];
            }
        } catch (\Throwable $e) {
            Log::error('Failed to resolve Telegram file URL: ' . $e->getMessage());
        }
        return '';
    }

    public function setWebhook(string $url): array
    {
        $token = $this->getBotToken();
        $res = Http::post("https://api.telegram.org/bot{$token}/setWebhook", ['url' => $url]);
        return $res->json();
    }

    public function getWebhookInfo(): array
    {
        $token = $this->getBotToken();
        $res = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        return $res->json();
    }

    protected function getMainMenuKeyboard(): array
    {
        return [
            'keyboard' => [
                [
                    ['text' => '📝 Send Feedback'],
                    ['text' => '📋 My Feedback'],
                ],
                [
                    ['text' => 'ℹ️ Help'],
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ];
    }

    protected function answerCallbackQuery(string $callbackQueryId): void
    {
        $token = $this->getBotToken();
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
        ]);
    }

    protected function normalizeCategory(string $text): string
    {
        if (str_contains(strtolower($text), 'bug')) return 'Bug';
        if (str_contains(strtolower($text), 'suggest')) return 'Suggestion';
        if (str_contains(strtolower($text), 'complain')) return 'Complaint';
        if (str_contains(strtolower($text), 'question')) return 'Question';
        return 'Other';
    }
}

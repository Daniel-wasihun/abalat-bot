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

    /**
     * EOTC Sunday School Localized Translations
     */
    protected function getTranslations(string $lang): array
    {
        $translations = [
            'am' => [
                'welcome' => "እንኳን ወደ ኢትዮጵያ ኦርቶዶክስ ተዋሕዶ ቤተክርስቲያን ሰንበት ትምህርት ቤት የአገልግሎት አስተያየትና ጥያቄ መቀበያ ቦት በደህና መጡ! ⛪\n\nእባክዎን ከታች ካሉት አማራጮች አንዱን ይምረጡ፦",
                'help' => "ℹ️ *የአጠቃቀም መመሪያ*\n\n• *📝 አስተያየት ለመስጠት* የሚለውን በመጫን ሃሳብዎን፣ ጥያቄዎን ወይም አስተያየትዎን ያጋሩ።\n• *📋 የላክኋቸው አስተያየቶች* የሚለውን በመጫን ከዚህ በፊት የላኳቸውን መመልከት ይችላሉ።\n• ቋንቋ ለመቀየር *🌐 ቋንቋ ቀይር / Language* የሚለውን ይጫኑ።",
                'choose_category' => "እባክዎን የአገልግሎት ዘርፉን ይምረጡ፦",
                'category_selected' => "የመረጡት ዘርፍ፦ *{category}*\n\nእባክዎን አሁን መልዕክትዎን ወይም አስተያየትዎን ይጻፉ። ፎቶ፣ ሰነድ ወይም የድምፅ መልዕክት ማያያዝ ይችላሉ።",
                'feedback_received' => "እግዚአብሔር ያክብርልን! አስተያየትዎ በስኬት ደርሶናል። በሰንበት ትምህርት ቤቱ አገልግሎት ክፍል ተገምግሞ ምላሽ ይሰጥዎታል:: 🙏",
                'no_feedback' => "እስካሁን ምንም የላኩት አስተያየት የለም። ለመጀመር *📝 አስተያየት ለመስጠት* የሚለውን ይጫኑ!",
                'feedback_list_title' => "📋 *የቅርብ ጊዜ አስተያየቶችዎ ዝርዝር፦*\n",
                'submitted_on' => "በ {date} ተልኳል",
                'invalid_input' => "ይቅርታ፣ መልዕክትዎ አልገባኝም። እባክዎን ከታች ያለውን ማውጫ ይጠቀሙ ወይም /start ይላኩ።",
                'send_feedback' => "📝 አስተያየት ለመስጠት",
                'my_feedback' => "📋 የላክኋቸው አስተያየቶች",
                'help_btn' => "ℹ️ እርዳታ",
                'lang_btn' => "🌐 ቋንቋ ቀይር / Language",
                'choose_lang' => "እባክዎን የሚመርጡትን ቋንቋ ይምረጡ / Please choose your language:",
                'language_changed' => "ቋንቋው ወደ *አማርኛ* ተቀይሯል።",
                'categories' => [
                    'Spiritual Education' => 'ትምህርተ ሃይማኖትና መንፈሳዊ ትምህርት',
                    'Choir & Hymns' => 'መዝሙርና ማኅሌት አገልግሎት',
                    'Liturgy & Service' => 'ሥርዓተ አምልኮና ቅዳሴ',
                    'General Inquiry' => 'አጠቃላይ ጥያቄና አስተያየት',
                    'Other' => 'ሌላ አገልግሎት',
                ]
            ],
            'om' => [
                'welcome' => "Baga Gara Botii Yaadaa fi Gaaffii Mana Barumsa Dilbataa Mana Kiristaanaa Ortodoxii Tawaahidoo Itoophiyaa nagaan dhuftan! ⛪\n\nMaaloo filannoowwan gadii keessaa tokko filadhaa:",
                'help' => "ℹ️ *Qajeelfama Fayyadamaa*\n\n• Yaada ykn gaaffii keessan qooduuf *📝 Yaada Erguu* kan jedhu cuqaasaa.\n• Yaada kanaan dura ergitan ilaaluuf *📋 Yaada Koo* kan jedhu cuqaasaa.\n• Qooqa jijjiiruuf *🌐 Qooqa Jijjiiri / Language* kan jedhu cuqaasaa.",
                'choose_category' => "Maaloo damee yaada keessanii filadhaa:",
                'category_selected' => "Damee filatame: *{category}*\n\nMaaloo ergaa keessan amma barreessaa ykn suuraa/sagalee/faayilii dabalaa.",
                'feedback_received' => "Waaqayyo isiniif haa kennu! Yaadin keessan nu gaheera. Geggeessitoota Mana Barumsa Dilbataatiin ilaalamee deebii argata. 🙏",
                'no_feedback' => "Kanaan dura yaada ergitan hin qabdu. Jalqabuuf *📝 Yaada Erguu* kan jedhu cuqaasaa!",
                'feedback_list_title' => "📋 *Tarree Yaada Keessanii Kanaan Duraa:*\n",
                'submitted_on' => "Guyyaa {date} ergame",
                'invalid_input' => "Dhiifama ergaan keessan naaf hin galle. Maaloo filannoowwan gadii fayyadamaa ykn /start ergaa.",
                'send_feedback' => "📝 Yaada Erguu",
                'my_feedback' => "📋 Yaada Koo",
                'help_btn' => "ℹ️ Gargaarsa",
                'lang_btn' => "🌐 Qooqa Jijjiiri / Language",
                'choose_lang' => "Maaloo qooqa keessan filadhaa / Please choose your language:",
                'language_changed' => "Qooqni keessan gara *Afaan Oromootti* jijjiirameera.",
                'categories' => [
                    'Spiritual Education' => 'Barumsa Macaafa Qulqulluu',
                    'Choir & Hymns' => 'Tajaajila Faarfannaa fi Maahleetii',
                    'Liturgy & Service' => 'Sirna Kadhannaa fi Tajaajila',
                    'General Inquiry' => 'Gaaffii fi Yaada Waliigalaa',
                    'Other' => 'Tajaajila Biroo',
                ]
            ],
            'en' => [
                'welcome' => "Welcome to the Ethiopian Orthodox Tewahedo Church Sunday School Feedback & Inquiry Bot! ⛪\n\nPlease select an option from the menu below:",
                'help' => "ℹ️ *Help & Usage Guide*\n\n• Press *📝 Send Feedback* to share your thoughts, comments, or questions.\n• Press *📋 My Feedback* to check your past submissions.\n• Press *🌐 Change Language* to switch preferred language.",
                'choose_category' => "Please select the category for your feedback:",
                'category_selected' => "Category selected: *{category}*\n\nPlease type your message now or attach an image/document/voice note.",
                'feedback_received' => "Thank you and God bless you! Your feedback has been received and will be reviewed by the Sunday School coordinators. 🙏",
                'no_feedback' => "You haven't submitted any feedback yet. Click *📝 Send Feedback* to start!",
                'feedback_list_title' => "📋 *Your Recent Submissions:*\n",
                'submitted_on' => "Submitted on {date}",
                'invalid_input' => "I didn't quite get that. Please use the menu below or send /start.",
                'send_feedback' => "📝 Send Feedback",
                'my_feedback' => "📋 My Feedback",
                'help_btn' => "ℹ️ Help",
                'lang_btn' => "🌐 Change Language",
                'choose_lang' => "Please choose your language:",
                'language_changed' => "Language changed to *English*.",
                'categories' => [
                    'Spiritual Education' => 'Spiritual Education',
                    'Choir & Hymns' => 'Choir & Hymns Service',
                    'Liturgy & Service' => 'Liturgy & Church Service',
                    'General Inquiry' => 'General Inquiry & Suggestions',
                    'Other' => 'Other Service',
                ]
            ]
        ];

        return $translations[$lang] ?? $translations['am'];
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

        // Retrieve existing user to preserve manual language selections
        $existingUser = $this->userRepo->findByTelegramId($telegramId);
        $userLanguage = $existingUser['language'] ?? null;

        if (!$userLanguage) {
            $telegramLocale = strtolower($from['language_code'] ?? '');
            if (in_array($telegramLocale, ['am', 'om', 'en'])) {
                $userLanguage = $telegramLocale;
            } else {
                $userLanguage = 'am'; // Default language is Amharic
            }
        }

        // Save or update Telegram user
        $user = $this->userRepo->createOrUpdateTelegramUser([
            'telegramId' => $telegramId,
            'chatId' => $chatId,
            'username' => $from['username'] ?? '',
            'firstName' => $from['first_name'] ?? '',
            'lastName' => $from['last_name'] ?? '',
            'language' => $userLanguage,
        ]);

        $text = trim($message['text'] ?? '');
        $userStateKey = "telegram_state_{$telegramId}";
        $currentState = Cache::get($userStateKey);

        // Language-agnostic triggers
        $isStart = ($text === '/start');
        $isHelp = ($text === '/help' || $text === 'ℹ️ Help' || $text === 'ℹ️ እርዳታ' || $text === 'ℹ️ Gargaarsa');
        $isFeedback = ($text === '/feedback' || str_contains($text, 'አስተያየት ለመስጠት') || str_contains($text, 'Yaada Erguu') || str_contains($text, 'Send Feedback'));
        $isMyFeedback = ($text === '/myfeedback' || str_contains($text, 'የላክኋቸው አስተያየቶች') || str_contains($text, 'Yaada Koo') || str_contains($text, 'My Feedback'));
        $isLang = ($text === '/language' || str_contains($text, 'ቋንቋ ቀይር') || str_contains($text, 'Qooqa Jijjiiri') || str_contains($text, 'Language'));

        if ($isStart) {
            Cache::forget($userStateKey);
            $this->sendWelcomeMessage($chatId, $user['firstName'] ?? 'User', $userLanguage);
            return;
        }

        if ($isHelp) {
            Cache::forget($userStateKey);
            $this->sendHelpMessage($chatId, $userLanguage);
            return;
        }

        if ($isMyFeedback) {
            Cache::forget($userStateKey);
            $this->sendUserFeedbackList($chatId, $user['id'], $userLanguage);
            return;
        }

        if ($isFeedback) {
            Cache::put($userStateKey, 'awaiting_category', 3600);
            $this->sendCategorySelectionPrompt($chatId, $userLanguage);
            return;
        }

        if ($isLang) {
            Cache::forget($userStateKey);
            $this->sendLanguageSelectionPrompt($chatId, $userLanguage);
            return;
        }

        // Check if user is in feedback submission flow
        if ($currentState && str_starts_with($currentState, 'awaiting_content:')) {
            $category = str_replace('awaiting_content:', '', $currentState);
            $this->processFeedbackSubmission($chatId, $user, $message, $category, $userLanguage);
            Cache::forget($userStateKey);
            return;
        }

        if ($currentState === 'awaiting_category') {
            $category = $this->normalizeCategory($text);
            Cache::put($userStateKey, "awaiting_content:{$category}", 3600);
            
            $trans = $this->getTranslations($userLanguage);
            $catLabel = $trans['categories'][$category] ?? $category;
            $msgPrompt = $userLanguage === 'am'
                ? "ዘርፍ፦ *{$catLabel}* ተመርጧል።\n\nእባክዎን አሁን መልዕክትዎን ወይም አስተያየትዎን ይጻፉ።"
                : ($userLanguage === 'om'
                    ? "Damee: *{$catLabel}* filatameera.\n\nMaaloo ergaa keessan amma barreessaa."
                    : "Category set to *{$catLabel}*.\n\nPlease type your feedback message now.");

            $this->sendMessage($chatId, $msgPrompt);
            return;
        }

        // Fallback message
        $trans = $this->getTranslations($userLanguage);
        $this->sendMessage(
            $chatId,
            $trans['invalid_input'],
            $this->getMainMenuKeyboard($userLanguage)
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

        // Fetch current user language
        $user = $this->userRepo->findByTelegramId($telegramId);
        $lang = $user['language'] ?? 'am';

        if (str_starts_with($data, 'lang:')) {
            $newLang = str_replace('lang:', '', $data);
            if ($user) {
                $this->userRepo->update($user['id'], ['language' => $newLang]);
            }
            $trans = $this->getTranslations($newLang);
            $this->sendMessage($chatId, $trans['language_changed'], $this->getMainMenuKeyboard($newLang));
            return;
        }

        if (str_starts_with($data, 'cat:')) {
            $category = str_replace('cat:', '', $data);
            Cache::put("telegram_state_{$telegramId}", "awaiting_content:{$category}", 3600);
            
            $trans = $this->getTranslations($lang);
            $catLabel = $trans['categories'][$category] ?? $category;
            
            $prompt = str_replace('{category}', $catLabel, $trans['category_selected']);
            $this->sendMessage($chatId, $prompt);
        }
    }

    protected function sendWelcomeMessage(string $chatId, string $firstName, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $msg = "ሰላም / Hello *{$firstName}*! 👋\n\n" . $trans['welcome'];
        $this->sendMessage($chatId, $msg, $this->getMainMenuKeyboard($lang));
    }

    protected function sendHelpMessage(string $chatId, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $this->sendMessage($chatId, $trans['help'], $this->getMainMenuKeyboard($lang));
    }

    protected function sendLanguageSelectionPrompt(string $chatId, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'አማርኛ (Amharic)', 'callback_data' => 'lang:am'],
                    ['text' => 'Afaan Oromoo (Oromifa)', 'callback_data' => 'lang:om'],
                ],
                [
                    ['text' => 'English (English)', 'callback_data' => 'lang:en'],
                ]
            ]
        ];
        $this->sendMessage($chatId, $trans['choose_lang'], $keyboard);
    }

    protected function sendCategorySelectionPrompt(string $chatId, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⛪ ' . $trans['categories']['Spiritual Education'], 'callback_data' => 'cat:Spiritual Education'],
                ],
                [
                    ['text' => '🎵 ' . $trans['categories']['Choir & Hymns'], 'callback_data' => 'cat:Choir & Hymns'],
                ],
                [
                    ['text' => '📜 ' . $trans['categories']['Liturgy & Service'], 'callback_data' => 'cat:Liturgy & Service'],
                ],
                [
                    ['text' => '❓ ' . $trans['categories']['General Inquiry'], 'callback_data' => 'cat:General Inquiry'],
                ],
                [
                    ['text' => '📝 ' . $trans['categories']['Other'], 'callback_data' => 'cat:Other'],
                ]
            ]
        ];

        $this->sendMessage($chatId, $trans['choose_category'], $keyboard);
    }

    protected function sendUserFeedbackList(string $chatId, string $userId, string $lang): void
    {
        $feedbacks = $this->feedbackRepo->getByUserId($userId);
        $trans = $this->getTranslations($lang);

        if (empty($feedbacks)) {
            $this->sendMessage($chatId, $trans['no_feedback']);
            return;
        }

        $lines = [$trans['feedback_list_title']];
        foreach (array_slice($feedbacks, 0, 5) as $idx => $f) {
            $statusEmoji = match(strtolower($f['status'] ?? '')) {
                'new' => '🟡',
                'in progress' => '🔵',
                'resolved', 'closed' => '🟢',
                default => '⚪',
            };
            $msgSnippet = mb_strimwidth($f['message'] ?? 'No text', 0, 40, '...');
            $date = date('Y-m-d H:i', strtotime($f['createdAt'] ?? 'now'));
            
            $dbCat = $f['category'] ?? 'Other';
            $catLabel = $trans['categories'][$dbCat] ?? $dbCat;
            
            $subText = str_replace('{date}', $date, $trans['submitted_on']);
            $lines[] = ($idx + 1) . ". {$statusEmoji} [{$catLabel}] *{$f['status']}*\n   \"{$msgSnippet}\"\n   _{$subText}_\n";
        }

        $this->sendMessage($chatId, implode("\n", $lines), $this->getMainMenuKeyboard($lang));
    }

    protected function processFeedbackSubmission(string $chatId, array $user, array $message, string $category, string $lang): void
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

        $trans = $this->getTranslations($lang);
        $this->sendMessage(
            $chatId,
            $trans['feedback_received'],
            $this->getMainMenuKeyboard($lang)
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

    protected function getMainMenuKeyboard(string $lang): array
    {
        $trans = $this->getTranslations($lang);
        return [
            'keyboard' => [
                [
                    ['text' => $trans['send_feedback']],
                    ['text' => $trans['my_feedback']],
                ],
                [
                    ['text' => $trans['help_btn']],
                    ['text' => $trans['lang_btn']],
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
        $lower = strtolower($text);
        if (str_contains($lower, 'ትምህርት') || str_contains($lower, 'education') || str_contains($lower, 'barumsa')) {
            return 'Spiritual Education';
        }
        if (str_contains($lower, 'መዝሙር') || str_contains($lower, 'ማኅሌት') || str_contains($lower, 'choir') || str_contains($lower, 'faarfa') || str_contains($lower, 'mezmur')) {
            return 'Choir & Hymns';
        }
        if (str_contains($lower, 'ሥርዓት') || str_contains($lower, 'ቅዳሴ') || str_contains($lower, 'liturgy') || str_contains($lower, 'sirna') || str_contains($lower, 'service')) {
            return 'Liturgy & Service';
        }
        if (str_contains($lower, 'ጥያቄ') || str_contains($lower, 'አስተያየት') || str_contains($lower, 'inquiry') || str_contains($lower, 'gaaffi') || str_contains($lower, 'suggest')) {
            return 'General Inquiry';
        }
        return 'Other';
    }
}

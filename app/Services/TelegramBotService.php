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
    protected string $botToken;

    public function __construct(
        protected UserRepositoryInterface     $userRepo,
        protected FeedbackRepositoryInterface $feedbackRepo,
        protected SettingRepositoryInterface  $settingRepo
    ) {
        $this->botToken = env('TELEGRAM_BOT_TOKEN', '');
    }

    public function getBotToken(): string
    {
        $customToken = $this->settingRepo->get('bot_token');
        return !empty($customToken) ? $customToken : $this->botToken;
    }

    /* ══════════════════════════════════════════════════════════
       Localisation — EOTC Sunday School (ሰንበት ትምህርት ቤት)
    ══════════════════════════════════════════════════════════ */

    protected function getTranslations(string $lang): array
    {
        $t = [
            'am' => [
                'welcome'              => "እንኳን ወደ *ደቂቀ ብርሃን ሰንበት ትምህርት ቤት* የአስተያየት ቦት በደህና መጡ! ⛪🙏\n\nእባክዎን ከታች ካሉት አማራጮች አንዱን ይምረጡ፦",
                'choose_lang_first'    => "🙏 *እንኳን ደህና መጡ!*\n\nወደ ደቂቀ ብርሃን ሰንበት ትምህርት ቤቱ ቦት እንኳን ደህና መጡ።\n\nእባክዎን ቋንቋ ይምረጡ / Please choose your language:",
                'help'                 => "ℹ️ *የአጠቃቀም መመሪያ*\n\n• *📝 አስተያየት ለመስጠት* — ሃሳብ፣ ጥያቄ ወይም ጸሎት ጥያቄ ይላኩ\n• *📋 የላክኋቸው አስተያየቶች* — ያስቀደሙ አስተያየቶች\n• *🌐 ቋንቋ ቀይር* — ቋንቋ ለመቀየር\n• */help* — ይህን ጽሑፍ ለማየት",
                'choose_category'      => "⛪ እባክዎን የሚሰጡትን አስተያየት ዓይነት ይምረጡ፦",
                'category_selected'    => "✅ ዘርፍ፦ *{category}*\n\nእባክዎን አሁን መልዕክትዎን ይጻፉ፦\n_(ፎቶ፣ ሰነድ ወይም የድምፅ መልዕክት ማያያዝ ይቻላል)_",
                'feedback_received'    => "✝️ *እግዚአብሔር ይባርክዎ!*\n\nአስተያየትዎ በተሳካ ሁኔታ ደርሶናል። በደቂቀ ብርሃን ሰንበት ትምህርት ቤቱ አስተዳዳሪዎች ተገምግሞ ምላሽ ይሰጥዎታል። 🙏",
                'no_feedback'          => "📭 እስካሁን ምንም አስተያየት አልተላኩም።\n\n*📝 አስተያየት ለመስጠት* ይጫኑ!",
                'feedback_list_title'  => "📋 *የቅርብ ጊዜ አስተያየቶችዎ፦*\n",
                'submitted_on'         => "📅 {date} ተልኳል",
                'invalid_input'        => "❓ መልዕክቱ ስህተት ይመስላል። እባክዎን ከታች ያለውን ማውጫ ይጠቀሙ ወይም */start* ይላኩ።",
                'send_feedback'        => "📝 አስተያየት ለመስጠት",
                'my_feedback'          => "📋 የላክኋቸው አስተያየቶች",
                'help_btn'             => "ℹ️ እርዳታ",
                'lang_btn'             => "🌐 ቋንቋ ቀይር",
                'choose_lang'          => "🌐 ቋንቋ ይምረጡ / Please choose your language:",
                'language_changed'     => "✅ ቋንቋው ወደ *አማርኛ* ተቀይሯል። 🇪🇹",
                'categories'           => [
                    'Spiritual Education' => '⛪ ትምህርተ ሃይማኖት',
                    'Choir & Hymns'       => '🎵 መዝሙርና ማኅሌት',
                    'Liturgy & Service'   => '📜 ሥርዓተ አምልኮ',
                    'Prayer Request'      => '🙏 ጸሎት ጥያቄ',
                    'General Inquiry'     => '❓ አጠቃላይ ጥያቄ',
                    'Other'               => '📝 ሌላ',
                ],
            ],

            'om' => [
                'welcome'              => "Baga Gara *Botii Yaadaa fi Gaaffii Mana Barumsa Dilbataa Mana Kiristaanaa Ortodoxii* nagaan dhuftan! ⛪🙏\n\nMaaloo filannoowwan gadii keessaa tokko filadhaa:",
                'choose_lang_first'    => "🙏 *Baga nagaan dhuftan!*\n\nBotii Mana Barumsa Dilbataatti baga nagaan dhuftan.\n\nMaaloo qooqa filadhaa / Please choose your language:",
                'help'                 => "ℹ️ *Qajeelfama Fayyadamaa*\n\n• *📝 Yaada Erguu* — Yaada, gaaffii ykn kadhannaa erguu\n• *📋 Yaada Koo* — Yaada kanaan dura ergitan ilaaluu\n• *🌐 Qooqa Jijjiiri* — Qooqa jijjiiruuf\n• */help* — Gargaarsa argatuuf",
                'choose_category'      => "⛪ Maaloo gosa yaada keessanii filadhaa:",
                'category_selected'    => "✅ Gosa: *{category}*\n\nMaaloo ergaa keessan amma barreessaa:\n_(Suuraa, faayilii ykn sagalee dabaluu ni dandeessu)_",
                'feedback_received'    => "✝️ *Waaqayyo isiniif haa kennu!*\n\nYaadin keessan nu gaheera. Geggeessitoota Mana Barumsa Dilbataatiin ilaalamee deebii argata. 🙏",
                'no_feedback'          => "📭 Kanaan dura yaada ergitan hin qabdu.\n\n*📝 Yaada Erguu* cuqaasaa!",
                'feedback_list_title'  => "📋 *Tarree Yaada Keessanii Kanaan Duraa:*\n",
                'submitted_on'         => "📅 Guyyaa {date} ergame",
                'invalid_input'        => "❓ Dhiifama, ergaan keessan naaf hin galle. Filannoowwan gadii fayyadamaa ykn */start* ergaa.",
                'send_feedback'        => "📝 Yaada Erguu",
                'my_feedback'          => "📋 Yaada Koo",
                'help_btn'             => "ℹ️ Gargaarsa",
                'lang_btn'             => "🌐 Qooqa Jijjiiri",
                'choose_lang'          => "🌐 Qooqa filadhaa / Please choose your language:",
                'language_changed'     => "✅ Qooqni keessan gara *Afaan Oromootti* jijjiirameera. 🇪🇹",
                'categories'           => [
                    'Spiritual Education' => '⛪ Barumsa Macaafa Qulqulluu',
                    'Choir & Hymns'       => '🎵 Tajaajila Faarfannaa',
                    'Liturgy & Service'   => '📜 Sirna Kadhannaa',
                    'Prayer Request'      => '🙏 Kadhaannaa',
                    'General Inquiry'     => '❓ Gaaffii Waliigalaa',
                    'Other'               => '📝 Kan Biroo',
                ],
            ],

            'en' => [
                'welcome'              => "Welcome to the *Ethiopian Orthodox Tewahedo Church Sunday School* Feedback Bot! ⛪🙏\n\nPlease select an option from the menu below:",
                'choose_lang_first'    => "🙏 *Welcome!*\n\nWelcome to the Sunday School Bot.\n\nPlease choose your preferred language:",
                'help'                 => "ℹ️ *Help & Usage Guide*\n\n• *📝 Send Feedback* — Share your thoughts, questions, or prayer requests\n• *📋 My Feedback* — View your past submissions\n• *🌐 Change Language* — Switch your preferred language\n• */help* — Show this message",
                'choose_category'      => "⛪ Please select the category for your feedback:",
                'category_selected'    => "✅ Category: *{category}*\n\nPlease type your message now:\n_(You may attach an image, document, or voice note)_",
                'feedback_received'    => "✝️ *God bless you!*\n\nYour feedback has been received and will be reviewed by the Sunday School coordinators. 🙏",
                'no_feedback'          => "📭 You haven't submitted any feedback yet.\n\nPress *📝 Send Feedback* to start!",
                'feedback_list_title'  => "📋 *Your Recent Submissions:*\n",
                'submitted_on'         => "📅 Submitted on {date}",
                'invalid_input'        => "❓ I didn't quite understand that. Please use the menu below or send */start*.",
                'send_feedback'        => "📝 Send Feedback",
                'my_feedback'          => "📋 My Feedback",
                'help_btn'             => "ℹ️ Help",
                'lang_btn'             => "🌐 Change Language",
                'choose_lang'          => "🌐 Please choose your language:",
                'language_changed'     => "✅ Language changed to *English*. 🇺🇸",
                'categories'           => [
                    'Spiritual Education' => '⛪ Spiritual Education',
                    'Choir & Hymns'       => '🎵 Choir & Hymns',
                    'Liturgy & Service'   => '📜 Liturgy & Church Service',
                    'Prayer Request'      => '🙏 Prayer Request',
                    'General Inquiry'     => '❓ General Inquiry',
                    'Other'               => '📝 Other',
                ],
            ],
        ];

        return $t[$lang] ?? $t['am'];
    }

    /* ══════════════════════════════════════════════════════════
       Webhook Entry Point
    ══════════════════════════════════════════════════════════ */

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
        $from    = $message['from'] ?? null;
        $chat    = $message['chat'] ?? null;

        if (!$from || !$chat) {
            return;
        }

        $telegramId = (string) $from['id'];
        $chatId     = (string) $chat['id'];

        // Retrieve existing user
        $existingUser = $this->userRepo->findByTelegramId($telegramId);
        $userLanguage = $existingUser['preferredLanguage']
            ?? $existingUser['language']
            ?? null;

        // Detect language on first contact
        if (!$userLanguage) {
            $telegramLocale = strtolower($from['language_code'] ?? '');
            $userLanguage   = in_array($telegramLocale, ['am', 'om', 'en']) ? $telegramLocale : 'am';
        }

        // Persist / update user
        $user = $this->userRepo->createOrUpdateTelegramUser([
            'telegramId'        => $telegramId,
            'chatId'            => $chatId,
            'username'          => $from['username']   ?? '',
            'firstName'         => $from['first_name'] ?? '',
            'lastName'          => $from['last_name']  ?? '',
            'preferredLanguage' => $userLanguage,
            'language'          => $userLanguage, // backward-compat
        ]);

        $text         = trim($message['text'] ?? '');
        $stateKey     = "tg_state_{$telegramId}";
        $firstStartKey = "tg_first_{$telegramId}";
        $currentState = Cache::get($stateKey);

        /* ── Command detection ──────────────────────────────── */
        $isStart      = $text === '/start';
        $isHelp       = in_array($text, ['/help', 'ℹ️ Help', 'ℹ️ እርዳታ', 'ℹ️ Gargaarsa']);
        $isFeedback   = $text === '/feedback'
            || str_contains($text, 'አስተያየት ለመስጠት')
            || str_contains($text, 'Yaada Erguu')
            || str_contains($text, 'Send Feedback');
        $isMyFeedback = $text === '/myfeedback'
            || str_contains($text, 'የላክኋቸው አስተያየቶች')
            || str_contains($text, 'Yaada Koo')
            || str_contains($text, 'My Feedback');
        $isLang       = $text === '/language'
            || str_contains($text, 'ቋንቋ ቀይር')
            || str_contains($text, 'Qooqa Jijjiiri')
            || str_contains($text, 'Change Language');

        /* ── /start → show language picker if first time ────── */
        if ($isStart) {
            Cache::forget($stateKey);
            $isFirstStart = !$existingUser || Cache::has($firstStartKey);

            if ($isFirstStart) {
                // Show language picker; after selection, welcome message follows
                Cache::put($firstStartKey, true, 3600);
                $this->sendLanguageSelectionPrompt($chatId, $userLanguage, true);
            } else {
                $this->sendWelcomeMessage($chatId, $user['firstName'] ?? '', $userLanguage);
            }
            return;
        }

        if ($isHelp) {
            Cache::forget($stateKey);
            $this->sendHelpMessage($chatId, $userLanguage);
            return;
        }

        if ($isMyFeedback) {
            Cache::forget($stateKey);
            $this->sendUserFeedbackList($chatId, $user['id'], $userLanguage);
            return;
        }

        if ($isFeedback) {
            Cache::put($stateKey, 'awaiting_category', 3600);
            $this->sendCategorySelectionPrompt($chatId, $userLanguage);
            return;
        }

        if ($isLang) {
            Cache::forget($stateKey);
            $this->sendLanguageSelectionPrompt($chatId, $userLanguage);
            return;
        }

        /* ── State machine ──────────────────────────────────── */
        if ($currentState && str_starts_with($currentState, 'awaiting_content:')) {
            $category = str_replace('awaiting_content:', '', $currentState);
            $this->processFeedbackSubmission($chatId, $user, $message, $category, $userLanguage);
            Cache::forget($stateKey);
            return;
        }

        if ($currentState === 'awaiting_category') {
            $category = $this->normalizeCategory($text);
            Cache::put($stateKey, "awaiting_content:{$category}", 3600);
            $trans    = $this->getTranslations($userLanguage);
            $catLabel = $trans['categories'][$category] ?? $category;
            $prompt   = str_replace('{category}', $catLabel, $trans['category_selected']);
            $this->sendMessage($chatId, $prompt);
            return;
        }

        /* ── Fallback ───────────────────────────────────────── */
        $trans = $this->getTranslations($userLanguage);
        $this->sendMessage($chatId, $trans['invalid_input'], $this->getMainMenuKeyboard($userLanguage));
    }

    /* ══════════════════════════════════════════════════════════
       Callback Query Handler
    ══════════════════════════════════════════════════════════ */

    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $id         = $callbackQuery['id'];
        $from       = $callbackQuery['from'];
        $message    = $callbackQuery['message'];
        $chatId     = (string) $message['chat']['id'];
        $telegramId = (string) $from['id'];
        $data       = $callbackQuery['data'] ?? '';

        $this->answerCallbackQuery($id);

        $user = $this->userRepo->findByTelegramId($telegramId);
        $lang = $user['preferredLanguage'] ?? $user['language'] ?? 'am';

        /* ── Language selection ──────────────────────────────── */
        if (str_starts_with($data, 'lang:')) {
            $newLang = str_replace('lang:', '', $data);
            if ($user) {
                $this->userRepo->update($user['id'], [
                    'preferredLanguage' => $newLang,
                    'language'          => $newLang,
                ]);
            }
            $trans = $this->getTranslations($newLang);

            // Clear first-start flag and send welcome
            Cache::forget("tg_first_{$telegramId}");
            $firstName = $user['firstName'] ?? '';
            $welcome   = "ሰላም / Hello *{$firstName}*! 👋\n\n" . $trans['welcome'];
            $this->sendMessage($chatId, $trans['language_changed']);
            $this->sendMessage($chatId, $welcome, $this->getMainMenuKeyboard($newLang));
            return;
        }

        /* ── Category selection ──────────────────────────────── */
        if (str_starts_with($data, 'cat:')) {
            $category = str_replace('cat:', '', $data);
            Cache::put("tg_state_{$telegramId}", "awaiting_content:{$category}", 3600);
            $trans    = $this->getTranslations($lang);
            $catLabel = $trans['categories'][$category] ?? $category;
            $prompt   = str_replace('{category}', $catLabel, $trans['category_selected']);
            $this->sendMessage($chatId, $prompt);
        }
    }

    /* ══════════════════════════════════════════════════════════
       Message Senders
    ══════════════════════════════════════════════════════════ */

    protected function sendWelcomeMessage(string $chatId, string $firstName, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $name  = !empty($firstName) ? "*{$firstName}*" : '';
        $msg   = ($name ? "ሰላም / Hello {$name}! 👋\n\n" : '') . $trans['welcome'];
        $this->sendMessage($chatId, $msg, $this->getMainMenuKeyboard($lang));
    }

    protected function sendHelpMessage(string $chatId, string $lang): void
    {
        $trans = $this->getTranslations($lang);
        $this->sendMessage($chatId, $trans['help'], $this->getMainMenuKeyboard($lang));
    }

    protected function sendLanguageSelectionPrompt(string $chatId, string $lang, bool $isFirst = false): void
    {
        $trans    = $this->getTranslations($lang);
        $prompt   = $isFirst ? $trans['choose_lang_first'] : $trans['choose_lang'];
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🇪🇹 አማርኛ (Amharic)',       'callback_data' => 'lang:am'],
                    ['text' => '🇺🇸 English',               'callback_data' => 'lang:en'],
                ],
                [
                    ['text' => '🇪🇹 Afaan Oromoo (Oromifa)', 'callback_data' => 'lang:om'],
                ],
            ],
        ];
        $this->sendMessage($chatId, $prompt, $keyboard);
    }

    protected function sendCategorySelectionPrompt(string $chatId, string $lang): void
    {
        $trans    = $this->getTranslations($lang);
        $cats     = $trans['categories'];
        $keyboard = [
            'inline_keyboard' => [
                [['text' => $cats['Spiritual Education'], 'callback_data' => 'cat:Spiritual Education']],
                [['text' => $cats['Choir & Hymns'],       'callback_data' => 'cat:Choir & Hymns']],
                [['text' => $cats['Liturgy & Service'],   'callback_data' => 'cat:Liturgy & Service']],
                [['text' => $cats['Prayer Request'],      'callback_data' => 'cat:Prayer Request']],
                [['text' => $cats['General Inquiry'],     'callback_data' => 'cat:General Inquiry']],
                [['text' => $cats['Other'],               'callback_data' => 'cat:Other']],
            ],
        ];
        $this->sendMessage($chatId, $trans['choose_category'], $keyboard);
    }

    protected function sendUserFeedbackList(string $chatId, string $userId, string $lang): void
    {
        $feedbacks = $this->feedbackRepo->getByUserId($userId);
        $trans     = $this->getTranslations($lang);

        if (empty($feedbacks)) {
            $this->sendMessage($chatId, $trans['no_feedback'], $this->getMainMenuKeyboard($lang));
            return;
        }

        $lines = [$trans['feedback_list_title']];
        foreach (array_slice($feedbacks, 0, 5) as $idx => $f) {
            $statusEmoji = match (strtolower($f['status'] ?? '')) {
                'new'                => '🟡',
                'read'               => '🔵',
                'in progress'        => '🟠',
                'resolved', 'closed' => '🟢',
                default              => '⚪',
            };
            $msgSnippet = mb_strimwidth($f['message'] ?? 'No text', 0, 50, '…');
            $date       = date('Y-m-d', strtotime($f['createdAt'] ?? 'now'));
            $catLabel   = $trans['categories'][$f['category'] ?? 'Other'] ?? ($f['category'] ?? 'Other');
            $subText    = str_replace('{date}', $date, $trans['submitted_on']);
            $lines[]    = ($idx + 1) . ". {$statusEmoji} *[{$catLabel}]*\n   \"{$msgSnippet}\"\n   _{$subText}_\n";
        }

        $this->sendMessage($chatId, implode("\n", $lines), $this->getMainMenuKeyboard($lang));
    }

    protected function processFeedbackSubmission(
        string $chatId,
        array  $user,
        array  $message,
        string $category,
        string $lang
    ): void {
        $type          = 'text';
        $content       = $message['text'] ?? $message['caption'] ?? '';
        $attachmentUrl = null;

        if (isset($message['photo'])) {
            $type          = 'image';
            $photo         = end($message['photo']);
            $attachmentUrl = $this->getFileUrl($photo['file_id']);
            if (empty($content)) $content = '[Image Attachment]';
        } elseif (isset($message['document'])) {
            $type          = 'document';
            $doc           = $message['document'];
            $attachmentUrl = $this->getFileUrl($doc['file_id']);
            if (empty($content)) $content = '[Document: ' . ($doc['file_name'] ?? 'file') . ']';
        } elseif (isset($message['voice'])) {
            $type          = 'voice';
            $voice         = $message['voice'];
            $attachmentUrl = $this->getFileUrl($voice['file_id']);
            if (empty($content)) $content = '[Voice Message: ' . ($voice['duration'] ?? 0) . 's]';
        }

        $this->feedbackRepo->create([
            'userId'           => $user['id'],
            'telegramId'       => $user['telegramId'],
            'userName'         => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')),
            'username'         => $user['username'] ?? '',
            'language'         => $lang,
            'type'             => $type,
            'message'          => $content,
            'attachmentUrl'    => $attachmentUrl,
            'category'         => $category,
            'priority'         => 'Medium',
            'status'           => 'New',
            'telegramMessageId' => $message['message_id'] ?? null,
            'replies'          => [],
            'internalNotes'    => [],
        ]);

        $trans = $this->getTranslations($lang);
        $this->sendMessage($chatId, $trans['feedback_received'], $this->getMainMenuKeyboard($lang));
    }

    /* ══════════════════════════════════════════════════════════
       Telegram API Wrappers
    ══════════════════════════════════════════════════════════ */

    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, ?int $replyToMessageId = null): bool
    {
        $token = $this->getBotToken();
        if (empty($token)) {
            Log::error('Telegram Bot Token is missing.');
            return false;
        }

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyToMessageId) {
            $payload['reply_to_message_id'] = (int) $replyToMessageId;
        }

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                $payload
            );

            if ($response->successful()) {
                return true;
            }

            $json = $response->json();
            $errorCode = $json['error_code'] ?? 0;

            // Rate limit hit (429): wait and retry once
            if ($errorCode === 429) {
                $retryAfter = $json['parameters']['retry_after'] ?? 1;
                sleep(min((int)$retryAfter, 5));
                $retryRes = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    $payload
                );
                if ($retryRes->successful()) {
                    return true;
                }
            }

            // Parse error (e.g. invalid Markdown entities): fallback to plain text sending
            if (isset($payload['parse_mode'])) {
                unset($payload['parse_mode']);
                $retryRes = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    $payload
                );
                return $retryRes->successful();
            }

            Log::error('Telegram sendMessage HTTP error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception: ' . $e->getMessage());
            return false;
        }
    }

    public function getFileUrl(string $fileId): string
    {
        $token = $this->getBotToken();
        try {
            $res = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getFile?file_id={$fileId}");
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
        $res   = Http::post("https://api.telegram.org/bot{$token}/setWebhook", ['url' => $url]);
        return $res->json();
    }

    public function getWebhookInfo(): array
    {
        $token = $this->getBotToken();
        $res   = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        return $res->json();
    }

    /* ══════════════════════════════════════════════════════════
       Keyboard Builders
    ══════════════════════════════════════════════════════════ */

    protected function getMainMenuKeyboard(string $lang): array
    {
        $t = $this->getTranslations($lang);
        return [
            'keyboard' => [
                [
                    ['text' => $t['send_feedback']],
                    ['text' => $t['my_feedback']],
                ],
                [
                    ['text' => $t['help_btn']],
                    ['text' => $t['lang_btn']],
                ],
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
            'persistent'        => true,
        ];
    }

    protected function answerCallbackQuery(string $callbackQueryId): void
    {
        $token = $this->getBotToken();
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
        ]);
    }

    /* ══════════════════════════════════════════════════════════
       Helpers
    ══════════════════════════════════════════════════════════ */

    protected function normalizeCategory(string $text): string
    {
        $lower = strtolower($text);
        if (str_contains($lower, 'ትምህርት') || str_contains($lower, 'education') || str_contains($lower, 'barumsa')) {
            return 'Spiritual Education';
        }
        if (str_contains($lower, 'መዝሙር') || str_contains($lower, 'choir') || str_contains($lower, 'faarfa')) {
            return 'Choir & Hymns';
        }
        if (str_contains($lower, 'ሥርዓት') || str_contains($lower, 'liturgy') || str_contains($lower, 'sirna')) {
            return 'Liturgy & Service';
        }
        if (str_contains($lower, 'ጸሎት') || str_contains($lower, 'prayer') || str_contains($lower, 'kadhannaa')) {
            return 'Prayer Request';
        }
        if (str_contains($lower, 'ጥያቄ') || str_contains($lower, 'inquiry') || str_contains($lower, 'gaaffi')) {
            return 'General Inquiry';
        }
        return 'Other';
    }
}

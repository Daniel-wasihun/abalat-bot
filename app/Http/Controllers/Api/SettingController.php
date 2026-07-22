<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    protected SettingRepositoryInterface $settingRepo;
    protected TelegramBotService $botService;

    public function __construct(SettingRepositoryInterface $settingRepo, TelegramBotService $botService)
    {
        $this->settingRepo = $settingRepo;
        $this->botService = $botService;
    }

    public function index()
    {
        $settings = $this->settingRepo->getAll();

        // Ensure defaults if empty
        return response()->json([
            'bot_token' => $settings['bot_token'] ?? env('TELEGRAM_BOT_TOKEN', ''),
            'webhook_url' => $settings['webhook_url'] ?? env('TELEGRAM_WEBHOOK_URL', ''),
            'welcome_message' => $settings['welcome_message'] ?? '',
            'feedback_categories' => $settings['feedback_categories'] ?? ['Bug', 'Suggestion', 'Complaint', 'Question', 'Other'],
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bot_token' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'welcome_message' => 'nullable|string',
            'feedback_categories' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->only(['bot_token', 'webhook_url', 'welcome_message', 'feedback_categories']) as $key => $val) {
            if ($val !== null) {
                $this->settingRepo->set($key, $val);
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function getWebhookStatus()
    {
        try {
            $info = $this->botService->getWebhookInfo();
            return response()->json($info);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to query Telegram Webhook info: ' . $e->getMessage()], 502);
        }
    }

    public function setupWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $url = $request->webhook_url;
        $this->settingRepo->set('webhook_url', $url);

        try {
            $res = $this->botService->setWebhook($url);
            return response()->json([
                'message' => 'Webhook registration payload sent to Telegram.',
                'telegram_response' => $res
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to register Telegram webhook: ' . $e->getMessage()], 502);
        }
    }
}

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

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->input('settings', []) as $key => $val) {
            $this->settingRepo->set($key, $val);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $this->settingRepo->getAll()
        ]);
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

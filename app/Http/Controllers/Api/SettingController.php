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

    public function getIdCardSettings()
    {
        $all = $this->settingRepo->getAll();
        $defaults = [
            'id_card.title_am'       => 'የደቂቀ ብርሃን ሰንበት ትምህርት ቤት መታወቂያ',
            'id_card.title_en'       => 'Dekike Birhan Senbet School ID Card',
            'id_card.title_or'       => 'Waraqaa Eenyummaa Mana Barumsaa Dekike Birhan Senbet',
            'id_card.authority_am'   => 'ሰጪው አካል',
            'id_card.authority_en'   => 'Issuing Authority',
            'id_card.authority_or'   => 'Qaama Kennaa',
            'id_card.id_prefix'      => 'DBSS',
            'id_card.validity_years' => 2,
            'id_card.logo'           => null,
        ];
        $result = [];
        foreach ($defaults as $key => $default) {
            $val = $all[$key] ?? $default;
            // Convert stored relative path to full URL for logo
            if ($key === 'id_card.logo' && $val) {
                $val = asset('storage/' . $val);
            }
            $result[$key] = $val;
        }
        return response()->json($result);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old logo if exists
        $existing = $this->settingRepo->get('id_card.logo');
        if ($existing) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($existing);
        }

        $path = $request->file('logo')->store('id_card', 'public');
        $this->settingRepo->set('id_card.logo', $path);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo'    => asset('storage/' . $path),
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

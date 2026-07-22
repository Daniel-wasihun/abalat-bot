<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramBotService $botService;

    public function __construct(TelegramBotService $botService)
    {
        $this->botService = $botService;
    }

    public function handle(Request $request)
    {
        $update = $request->all();

        if (empty($update)) {
            return response()->json(['message' => 'Empty payload'], 400);
        }

        try {
            $this->botService->handleWebhookUpdate($update);
        } catch (\Throwable $e) {
            Log::error('TelegramWebhookController Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            // Return 200 to Telegram to prevent it from retrying repeatedly
            return response()->json(['message' => 'Processed with errors', 'error' => $e->getMessage()], 200);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}

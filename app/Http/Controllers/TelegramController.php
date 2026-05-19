<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function __construct(private TelegramService $telegram) {}

    public function webhook(Request $request): Response
    {
        try {
            $allowedChatId = config('services.telegram.allowed_chat_id');

            $payload = $request->all();
            $chatId  = $payload['message']['chat']['id']
                    ?? $payload['callback_query']['message']['chat']['id']
                    ?? null;

            if ($allowedChatId && $chatId && (string) $chatId !== (string) $allowedChatId) {
                Log::warning('[Telegram] Unauthorized chat_id: ' . $chatId);
                return response('OK', 200);
            }

            $this->telegram->handleWebhook($payload);
        } catch (\Throwable $e) {
            Log::error('[Telegram] Webhook exception: ' . $e->getMessage());
        }

        // Always return 200 to Telegram
        return response('OK', 200);
    }
}

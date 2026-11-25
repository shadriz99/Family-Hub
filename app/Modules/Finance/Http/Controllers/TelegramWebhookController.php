<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        $expectedSecret = (string) config('telegram.webhook_secret', '');

        if ($expectedSecret !== '') {
            $receivedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $payload = $request->all();
        if (empty($payload)) {
            return response()->json(['ok' => false, 'error' => 'Empty payload'], 400);
        }

        $result = $botService->handleUpdate($payload);

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }
}

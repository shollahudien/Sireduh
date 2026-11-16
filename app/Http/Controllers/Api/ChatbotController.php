<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(protected ChatbotService $chatbot) {}

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required', 'in:user,bot'],
            'history.*.text' => ['required', 'string', 'max:500'],
            'user_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'session_id' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'reply' => $this->chatbot->ask(
                $validated['message'],
                $validated['history'] ?? [],
                $validated['user_name'] ?? null,
                $validated['session_id'] ?? null,
            ),
        ]);
    }
}

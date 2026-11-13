<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TextToSpeechService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suara AI untuk Teduh Bot — teks jawaban bot menjadi audio natural (Gemini TTS).
 *
 * Bila suara AI tidak tersedia (kuota/koneksi), kembalikan 503 agar
 * widget otomatis memakai suara mesin browser sebagai cadangan.
 */
class ChatbotSpeakController extends Controller
{
    public function speak(Request $request, TextToSpeechService $tts): Response|JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:2', 'max:600'],
        ]);

        $wav = $tts->synthesize($validated['text']);

        if ($wav === null) {
            return response()->json([
                'error' => 'Suara AI sedang tidak tersedia, memakai suara cadangan.',
            ], 503);
        }

        return response($wav, 200, [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

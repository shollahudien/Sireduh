<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Sintesis suara AI untuk Teduh Bot memakai Gemini TTS (natural voice).
 *
 * Keluaran PCM 16-bit mono 24 kHz dari Gemini dibungkus menjadi WAV
 * agar bisa langsung diputar browser tanpa library tambahan.
 * Hasil di-cache per teks+model+voice supaya kuota gratis hemat
 * (jawaban FAQ sering berulang).
 */
class TextToSpeechService
{
    public const CACHE_PREFIX = 'tts:audio:';

    private const SAMPLE_RATE = 24000;

    public function synthesize(string $text): ?string
    {
        $text = trim($text);

        if ($text === '' || ! mb_check_encoding($text, 'UTF-8')) {
            return null;
        }

        $text = mb_substr($text, 0, 600);
        $model = (string) config('services.gemini.tts_model');
        $voice = (string) config('services.gemini.tts_voice');

        $cacheKey = self::CACHE_PREFIX.md5($model.'|'.$voice.'|'.$text);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $models = array_values(array_filter([
            $model,
            (string) config('services.gemini.tts_fallback_model'),
        ]));

        foreach ($models as $candidate) {
            $pcm = $this->requestPcm($candidate, $text);

            if ($pcm !== null) {
                $wav = $this->pcmToWav($pcm, self::SAMPLE_RATE);
                Cache::put($cacheKey, $wav, now()->addDay());

                return $wav;
            }
        }

        return null;
    }

    /**
     * Panggil Gemini TTS dan kembalikan data PCM mentah (base64 sudah didekode).
     */
    private function requestPcm(string $model, string $text): ?string
    {
        $key = (string) config('services.gemini.key', '');

        if ($key === '' || $model === '') {
            return null;
        }

        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');

        $response = Http::timeout(25)
            ->withHeaders(['x-goog-api-key' => $key])
            ->post("{$baseUrl}/models/{$model}:generateContent", [
                'contents' => [
                    ['parts' => [['text' => $text]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => [
                        'voiceConfig' => [
                            'prebuiltVoiceConfig' => [
                                'voiceName' => (string) config('services.gemini.tts_voice'),
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json('candidates.0.content.parts.0.inlineData.data');

        if (! is_string($data) || $data === '') {
            return null;
        }

        $pcm = base64_decode($data, true);

        return $pcm === false || $pcm === '' ? null : $pcm;
    }

    /**
     * Bungkus PCM 16-bit mono menjadi kontainer WAV (header RIFF 44 byte).
     */
    private function pcmToWav(string $pcm, int $sampleRate): string
    {
        $dataLength = strlen($pcm);

        $header = 'RIFF'
            .pack('V', 36 + $dataLength)
            .'WAVE'
            .'fmt '
            .pack('V', 16)          // ukuran chunk fmt
            .pack('v', 1)           // format PCM
            .pack('v', 1)           // mono
            .pack('V', $sampleRate) // sample rate
            .pack('V', $sampleRate * 2) // byte rate (mono 16-bit)
            .pack('v', 2)           // block align
            .pack('v', 16)          // bits per sample
            .'data'
            .pack('V', $dataLength);

        return $header.$pcm;
    }
}

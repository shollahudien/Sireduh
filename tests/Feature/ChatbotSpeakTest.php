<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotSpeakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.tts_model' => 'gemini-3.8-flash-lite-tts',
            'services.gemini.tts_fallback_model' => 'gemini-2.5-flash-preview-tts',
        ]);

        Cache::flush();
    }

    public function test_speak_returns_wav_audio_from_gemini_tts(): void
    {
        // PCM 16-bit sederhana (24 sampel nol = 1 ms)
        $pcm = str_repeat("\x00\x00", 2400);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [
                        ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode($pcm)]],
                    ]]],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/chat/speak', [
            'text' => 'Halo, saya Teduh Bot.',
        ]);

        $response->assertOk();
        $this->assertSame('audio/wav', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringStartsWith('RIFF', $body);
        $this->assertSame('WAVE', substr($body, 8, 4));
        $this->assertStringContainsString('data', substr($body, 36, 4));

        // Header WAV benar: mono, 24 kHz, 16-bit
        $header = unpack('vformat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbits', substr($body, 20, 16));
        $this->assertSame(1, $header['format']);
        $this->assertSame(1, $header['channels']);
        $this->assertSame(24000, $header['sampleRate']);
        $this->assertSame(16, $header['bits']);

        // Prompt gaya memakai voice wanita + logat ringan
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'gemini-3.8-flash-lite-tts')
                && ($request['generationConfig']['responseModalities'][0] ?? '') === 'AUDIO'
                && isset($request['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName']);
        });

        // Teks dikirim apa adanya (tanpa awalan "Bacakan …") agar tidak ikut dibacakan
        Http::assertSent(function ($request): bool {
            $sent = $request['contents'][0]['parts'][0]['text'] ?? '';

            return $sent === 'Halo, saya Teduh Bot.';
        });
    }

    public function test_speak_falls_back_to_secondary_model_when_primary_fails(): void
    {
        $pcm = str_repeat("\x01\x00", 1200);
        $calls = 0;

        Http::fake(function ($request) use (&$calls, $pcm) {
            $calls++;

            if (str_contains($request->url(), 'gemini-3.8-flash-lite-tts')) {
                return Http::response(['error' => ['message' => 'model overloaded']], 429);
            }

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [
                        ['inlineData' => ['data' => base64_encode($pcm)]],
                    ]]],
                ],
            ], 200);
        });

        $response = $this->postJson('/api/chat/speak', ['text' => 'Tes cadangan model.']);

        $response->assertOk();
        $this->assertStringStartsWith('RIFF', $response->getContent());
        $this->assertSame(2, $calls);
    }

    public function test_speak_returns_503_when_tts_unavailable(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429),
        ]);

        $this->postJson('/api/chat/speak', ['text' => 'Halo dunia.'])
            ->assertStatus(503)
            ->assertJsonStructure(['error']);
    }

    public function test_speak_revalidates_text(): void
    {
        $this->postJson('/api/chat/speak', ['text' => 'a'])->assertStatus(422);
        $this->postJson('/api/chat/speak', [])->assertStatus(422);
    }

    public function test_speak_serves_cached_audio_without_calling_gemini_again(): void
    {
        $pcm = str_repeat("\x02\x00", 800);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['inlineData' => ['data' => base64_encode($pcm)]]]]],
                ],
            ], 200),
        ]);

        $first = $this->postJson('/api/chat/speak', ['text' => 'Pesan yang sama persis.']);
        $second = $this->postJson('/api/chat/speak', ['text' => 'Pesan yang sama persis.']);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->getContent(), $second->getContent());

        Http::assertSentCount(1);
    }
}

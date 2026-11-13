<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\Visitor;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Kunci Gemini disimulasikan untuk semua test; per-test dapat menimpanya.
        config(['services.gemini.key' => 'test-gemini-key']);
    }

    public function test_gemini_reply_is_used_when_api_succeeds(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Halo! Saya Teduh Bot. 🏕️']]]],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/chat', ['message' => 'halo teduh']);

        $response->assertOk()->assertJsonPath('reply', 'Halo! Saya Teduh Bot. 🏕️');

        // Pastikan prompt sistem berisi identitas & konteks bisnis
        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, 'Teduh Bot') && str_contains($system, 'KONTEKS BISNIS');
        });
    }

    public function test_falls_back_to_knowledge_base_when_gemini_fails(): void
    {
        Unit::create([
            'name' => 'Tenda Keluarga',
            'type' => 'glamping',
            'price' => 450000,
            'capacity' => 4,
            'stock' => 2,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('internal error', 500),
        ]);

        $response = $this->postJson('/api/chat', ['message' => 'Berapa harga glamping?']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('Tenda Keluarga', $reply);
        $this->assertStringContainsString('450.000', $reply);
    }

    public function test_works_without_api_key_via_fallback(): void
    {
        config(['services.gemini.key' => null]);

        Http::fake();

        $response = $this->postJson('/api/chat', ['message' => 'cara reservasi gimana ya']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('/checkout', $reply);
        Http::assertNothingSent(); // Tidak ada panggilan HTTP keluar tanpa key
    }

    public function test_unknown_question_gets_polite_default_answer(): void
    {
        config(['services.gemini.key' => null]);

        $response = $this->postJson('/api/chat', ['message' => 'cuaca besok bagaimana ya']);

        $response->assertOk();
        $this->assertStringContainsString('belum memahami', (string) $response->json('reply'));
    }

    public function test_message_is_required_and_limited(): void
    {
        $this->postJson('/api/chat', ['message' => ''])->assertStatus(422);
        $this->postJson('/api/chat', ['message' => str_repeat('a', 501)])->assertStatus(422);
        $this->postJson('/api/chat', ['message' => str_repeat('a', 500)])->assertOk();
    }

    public function test_html_in_question_is_sanitized_before_sending_to_ai(): void
    {
        config(['services.gemini.key' => null]);

        Http::fake();

        $response = $this->postJson('/api/chat', ['message' => '<script>alert(1)</script> halo']);

        $response->assertOk();
        Http::assertNothingSent();
    }

    // ===== Ketersediaan per tanggal =====

    private function createUnit(string $name, string $type, float $price, int $stock): Unit
    {
        return Unit::create([
            'name' => $name,
            'type' => $type,
            'price' => $price,
            'capacity' => 4,
            'stock' => $stock,
        ]);
    }

    private function createBookedReservation(Unit $unit, string $checkIn, string $checkOut): Reservation
    {
        $visitor = Visitor::create(['name' => 'Tamu Penuh', 'whatsapp_number' => '628999888777']);

        return Reservation::create([
            'order_code' => 'SIRES-BOTTEST1',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'total_price' => 500000,
            'status' => 'pending',
        ]);
    }

    public function test_availability_fallback_answers_with_real_data_for_indonesian_date(): void
    {
        config(['services.gemini.key' => null]);

        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);
        $this->createUnit('Aula Serbaguna', 'aula', 1000000, 1);

        $expectedDate = Carbon::parse('2026-12-20')->translatedFormat('d M Y');

        $response = $this->postJson('/api/chat', ['message' => 'apakah glamping tersedia 20 desember 2026?']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString($expectedDate, $reply);
        $this->assertStringContainsString('Tenda Keluarga', $reply);
        $this->assertStringContainsString('450.000', $reply);
        $this->assertStringContainsString('tersedia 2', $reply);
        $this->assertStringContainsString('/checkout', $reply);
    }

    public function test_availability_fallback_reports_full_unit_when_booked(): void
    {
        config(['services.gemini.key' => null]);

        $unit = $this->createUnit('Tenda Eksklusif', 'glamping', 500000, 1);
        $this->createBookedReservation($unit, '2026-12-20', '2026-12-21');

        $response = $this->postJson('/api/chat', ['message' => '20 desember 2026 masih ada tenda?']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('Semua unit penuh', $reply);
        $this->assertStringContainsString('PENUH', $reply);
    }

    public function test_availability_fallback_counts_nights_and_total(): void
    {
        config(['services.gemini.key' => null]);

        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        $response = $this->postJson('/api/chat', ['message' => '20 desember 2026 menginap 2 malam ada?']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('2 malam', $reply);
        $this->assertStringContainsString('900.000', $reply); // 450.000 x 2
        $this->assertStringContainsString('22 Dec 2026', $reply);
    }

    public function test_availability_fallback_gives_7day_overview_without_date(): void
    {
        config(['services.gemini.key' => null]);

        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        $response = $this->postJson('/api/chat', ['message' => 'ketersediaan kosong tanggal apa saja ya?']);

        $response->assertOk();
        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('7 hari ke depan', $reply);
        $this->assertStringContainsString('Tenda Keluarga', $reply);
        $this->assertStringContainsString('20 desember', $reply); // contoh format tanggal
    }

    public function test_besok_is_parsed_as_tomorrow(): void
    {
        config(['services.gemini.key' => null]);

        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        $expectedDate = Carbon::tomorrow()->translatedFormat('d M Y');

        $response = $this->postJson('/api/chat', ['message' => 'besok masih ada tenda?']);

        $response->assertOk();
        $this->assertStringContainsString($expectedDate, (string) $response->json('reply'));
    }

    public function test_gemini_context_includes_availability_when_date_detected(): void
    {
        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Tersedia!']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => 'glamping 20 desember 2026 kosong?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, 'KETERSEDIAAN TERKINI')
                && str_contains($system, 'Tenda Keluarga')
                && str_contains($system, '2 tersedia');
        });
    }

    // ===== Sinkronisasi harga dengan database =====

    public function test_unit_price_change_invalidates_chatbot_context(): void
    {
        // Simulasi konteks lama yang sudah basi
        Cache::put(ChatbotService::CONTEXT_CACHE_KEY, 'KONTEKS LAMA', 300);
        $this->assertTrue(Cache::has(ChatbotService::CONTEXT_CACHE_KEY));

        $unit = $this->createUnit('Tenda Keluarga', 'glamping', 500000, 2);
        $unit->update(['price' => 800000]);

        $this->assertFalse(Cache::has(ChatbotService::CONTEXT_CACHE_KEY));
    }

    public function test_product_price_change_invalidates_chatbot_context(): void
    {
        Cache::put(ChatbotService::CONTEXT_CACHE_KEY, 'KONTEKS LAMA', 300);

        $product = Product::create([
            'name' => 'Kopi Teduh',
            'category' => 'minuman',
            'price' => 15000,
            'type' => 'addon',
            'stock' => 10,
        ]);
        $product->update(['price' => 20000]);

        $this->assertFalse(Cache::has(ChatbotService::CONTEXT_CACHE_KEY));
    }

    public function test_gemini_price_context_reflects_db_change_immediately(): void
    {
        $unit = $this->createUnit('Tenda Keluarga', 'glamping', 500000, 2);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'ok']]]]],
            ], 200),
        ]);

        // Pertanyaan pertama: harga lama
        $this->postJson('/api/chat', ['message' => 'harga glamping berapa?']);

        // Admin mengubah harga di DB
        $unit->update(['price' => 800000]);

        // Pertanyaan kedua: harga baru harus langsung terlihat (tanpa menunggu cache)
        $this->postJson('/api/chat', ['message' => 'harga glamping berapa?']);

        $requests = collect(Http::recorded())->map(fn (array $pair) => $pair[0]['systemInstruction']['parts'][0]['text'] ?? '');

        $this->assertStringContainsString('500.000', $requests[0]);
        $this->assertStringNotContainsString('800.000', $requests[0]);
        $this->assertStringContainsString('800.000', $requests[1]);
        $this->assertStringNotContainsString('500.000', $requests[1]);
    }
}

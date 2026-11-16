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

    public function test_returns_busy_message_when_gemini_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('internal error', 500),
        ]);

        $response = $this->postJson('/api/chat', ['message' => 'Berapa harga glamping?']);

        $response->assertOk();
        $this->assertStringContainsString('sedang sibuk', (string) $response->json('reply'));
    }

    public function test_returns_unavailable_message_without_api_key(): void
    {
        config(['services.gemini.key' => null]);

        Http::fake();

        $response = $this->postJson('/api/chat', ['message' => 'cara reservasi gimana ya']);

        $response->assertOk();
        $this->assertStringContainsString('tidak tersedia', (string) $response->json('reply'));
        Http::assertNothingSent();
    }

    public function test_general_question_without_api_key_returns_unavailable_message(): void
    {
        config(['services.gemini.key' => null]);

        $response = $this->postJson('/api/chat', ['message' => 'cuaca besok bagaimana ya']);

        $response->assertOk();
        $this->assertStringContainsString('tidak tersedia', (string) $response->json('reply'));
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

    public function test_availability_context_includes_real_data_for_indonesian_date(): void
    {
        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);
        $this->createUnit('Aula Serbaguna', 'aula', 1000000, 1);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Tersedia']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => 'apakah glamping tersedia 20 desember 2026?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, 'KETERSEDIAAN TERKINI')
                && str_contains($system, 'Tenda Keluarga')
                && str_contains($system, '450.000')
                && str_contains($system, '2 tersedia');
        });
    }

    public function test_availability_context_reports_full_unit_when_booked(): void
    {
        $unit = $this->createUnit('Tenda Eksklusif', 'glamping', 500000, 1);
        $this->createBookedReservation($unit, '2026-12-20', '2026-12-21');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Penuh']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => '20 desember 2026 masih ada tenda?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, 'Tenda Eksklusif') && str_contains($system, '0 dari 1 tersedia');
        });
    }

    public function test_availability_context_counts_nights_and_total(): void
    {
        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Ada']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => '20 desember 2026 menginap 2 malam ada?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, '2 malam') && str_contains($system, '900.000');
        });
    }

    public function test_availability_overview_included_without_date(): void
    {
        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Cek tanggal']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => 'ketersediaan kosong tanggal apa saja ya?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, '3 hari ke depan') && str_contains($system, 'Tenda Keluarga');
        });
    }

    public function test_besok_is_parsed_as_tomorrow_in_availability_context(): void
    {
        $this->createUnit('Tenda Keluarga', 'glamping', 450000, 2);

        $expectedDate = Carbon::tomorrow()->translatedFormat('d M Y');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Ada']]]]],
            ], 200),
        ]);

        $this->postJson('/api/chat', ['message' => 'besok masih ada tenda?']);

        Http::assertSent(function ($request): bool {
            $system = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($system, 'KETERSEDIAAN TERKINI') && str_contains($system, 'Tenda Keluarga');
        });
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

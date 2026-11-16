<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload reservasi tiket-only (tanpa unit) untuk pengujian nomor WhatsApp.
     */
    private function validPayload(string $whatsapp, string $name = 'Budi Santoso'): array
    {
        $product = Product::create([
            'name' => 'Tiket Masuk WhatsApp Test',
            'category' => 'tiket_masuk',
            'price' => 15000,
            'type' => 'addon',
            'stock' => 100,
        ]);

        return [
            'visitor_name' => $name,
            'visitor_whatsapp' => $whatsapp,
            'check_in_date' => now()->addDay()->toDateString(),
            'addons' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];
    }

    public function test_local_format_is_normalized_to_international(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload('081234567890'));

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertSame(1, Visitor::count());
        $this->assertSame('6281234567890', Visitor::first()->whatsapp_number);
    }

    public function test_plus_62_and_punctuation_are_normalized_and_deduped(): void
    {
        $first = $this->postJson('/api/reservations', $this->validPayload('081234567890'));
        $first->assertStatus(201);

        // Format berbeda (spasi, dash, +62) tapi nomor yang sama → visitor sama
        $second = $this->postJson('/api/reservations', $this->validPayload('+62 812-3456-7890', 'Budi Santoso II'));
        $second->assertStatus(201);

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Visitor::where('whatsapp_number', '6281234567890')->count());
        $this->assertSame('Budi Santoso II', Visitor::where('whatsapp_number', '6281234567890')->value('name'));
    }

    public function test_62_prefix_without_leading_eight_is_rejected(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload('62123456789'));

        $response->assertStatus(422);
        $this->assertStringContainsString('08', (string) $response->json('errors.visitor_whatsapp.0'));
    }

    public function test_short_or_letter_containing_numbers_are_rejected(): void
    {
        foreach (['0812xxxx', '12345', '0812'] as $invalid) {
            $response = $this->postJson('/api/reservations', $this->validPayload($invalid));

            $response->assertStatus(422);
            $this->assertNotNull($response->json('errors.visitor_whatsapp.0'));
        }

        $this->assertSame(0, Visitor::count());
    }

    public function test_foreign_number_is_rejected(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload('+447911123456'));

        $response->assertStatus(422);
        $this->assertStringContainsString('08 atau 62', (string) $response->json('errors.visitor_whatsapp.0'));
        $this->assertSame(0, Visitor::count());
    }

    public function test_empty_whatsapp_gets_clear_indonesian_message(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload(''));

        $response->assertStatus(422);
        $this->assertStringContainsString('wajib diisi', (string) $response->json('errors.visitor_whatsapp.0'));
    }
}

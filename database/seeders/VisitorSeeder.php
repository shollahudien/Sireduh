<?php

namespace Database\Seeders;

use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        $visitors = [
            ['name' => 'Test Visitor', 'whatsapp_number' => '081234567890', 'email' => 'visitor@example.com'],
            ['name' => 'Admin', 'whatsapp_number' => '081234567891', 'email' => 'admin@example.com'],
        ];

        foreach ($visitors as $visitor) {
            Visitor::updateOrCreate(
                ['whatsapp_number' => $visitor['whatsapp_number']],
                $visitor,
            );
        }
    }
}

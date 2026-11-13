<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi nomor WhatsApp Indonesia:
 * menerima 08xx, 8xx, 62 8xx, +62 8xx (spasi/dash diabaikan),
 * dinormalisasi menjadi format internasional 628xxxxxxxxx.
 */
class WhatsAppNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail(self::message($value));
        }
    }

    /**
     * Normalisasi nomor WhatsApp Indonesia ke format 62xxxxxxxxx.
     * Mengembalikan null bila nomor tidak valid.
     */
    public static function normalize(mixed $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            // Sudah format internasional
        } elseif (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        // Harus nomor seluler Indonesia: diawali 628
        if (! str_starts_with($digits, '628')) {
            return null;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * Pesan kesalahan spesifik dalam Bahasa Indonesia.
     */
    public static function message(mixed $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return 'Nomor WhatsApp wajib diisi (contoh: 081234567890).';
        }

        if (! str_starts_with($digits, '0') && ! str_starts_with($digits, '62') && ! str_starts_with($digits, '8')) {
            return 'Nomor WhatsApp harus diawali 08 atau 62 (contoh: 081234567890).';
        }

        return 'Nomor WhatsApp tidak valid. Gunakan 10–15 digit angka, diawali 08 atau 62 (contoh: 081234567890).';
    }
}

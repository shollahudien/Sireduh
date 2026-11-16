<?php

namespace App\Services;

use App\Models\AulaPackage;

class AulaPackageService
{
    /** @return array<int, array{id:string,name:string,duration_hours:int,price:float}> */
    public static function all(): array
    {
        try {
            return AulaPackage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (AulaPackage $p): array => [
                    'id' => (string) $p->code,
                    'name' => (string) $p->name,
                    'duration_hours' => (int) $p->duration_hours,
                    'price' => (float) $p->price,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array{id:string,name:string,duration_hours:int,price:float}|null */
    public static function find(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        foreach (self::all() as $package) {
            if ($package['id'] === $id) {
                return $package;
            }
        }

        return null;
    }
}

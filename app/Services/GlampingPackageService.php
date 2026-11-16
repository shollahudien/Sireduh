<?php

namespace App\Services;

use App\Models\GlampingPackage;

class GlampingPackageService
{
    /** @return array<int, array{id:string,name:string,duration_hours:int,price:float}> */
    public static function all(): array
    {
        try {
            return GlampingPackage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (GlampingPackage $p): array => [
                    'id' => (string) $p->code,
                    'name' => (string) $p->name,
                    'duration_hours' => (int) $p->duration_hours,
                    'price' => (float) $p->price,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return array_map(fn (array $p): array => [
                'id' => (string) $p['id'],
                'name' => (string) $p['name'],
                'duration_hours' => (int) $p['duration_hours'],
                'price' => (float) $p['price'],
            ], config('glamping_packages', []));
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

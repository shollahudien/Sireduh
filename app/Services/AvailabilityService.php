<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AvailabilityService
{
    public const BLOCKING_STATUSES = ['pending', 'waiting_verification', 'paid', 'confirmed'];

    /** @return Collection<int, Unit> */
    public function availableUnits(Carbon $checkIn, Carbon $checkOut, ?string $type = null): Collection
    {
        $query = Unit::query()->where('stock', '>', 0)->orderBy('name');

        if ($type !== null) {
            $query->where('type', $type);
        }

        $booked = $this->bookedCountPerUnit($checkIn, $checkOut);

        return $query->get()
            ->filter(fn (Unit $unit): bool => $unit->stock - ($booked[$unit->id] ?? 0) > 0)
            ->values();
    }

    /** @return array<int, int> */
    public function bookedCountPerUnit(Carbon $checkIn, Carbon $checkOut): array
    {
        return Reservation::query()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $checkOut->toDateString())
            ->where('check_out_date', '>', $checkIn->toDateString())
            ->selectRaw('unit_id, COUNT(*) as aggregate')
            ->groupBy('unit_id')
            ->pluck('aggregate', 'unit_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function lockUnitForStay(int $unitId, Carbon $checkIn, Carbon $checkOut): Unit
    {
        $unit = Unit::query()->whereKey($unitId)->lockForUpdate()->firstOrFail();

        $overlapping = Reservation::query()
            ->where('unit_id', $unit->id)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $checkOut->toDateString())
            ->where('check_out_date', '>', $checkIn->toDateString())
            ->count();

        if ($unit->stock - $overlapping <= 0) {
            throw new \RuntimeException('Unit sudah penuh pada tanggal yang dipilih.');
        }

        return $unit;
    }
}

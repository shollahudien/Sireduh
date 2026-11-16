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

    /** @return array{0: string, 1: string} */
    protected function overlapExpressions(): array
    {
        $driver = Reservation::query()->getModel()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // check_in_date bisa tersimpan sebagai 'YYYY-MM-DD' atau 'YYYY-MM-DD 00:00:00' (cast date),
            // jadi normalisasi ke tanggal saja agar datetime() valid untuk deteksi overlap.
            $startExpr = "datetime(date(check_in_date) || ' ' || COALESCE(time(check_in_time),'00:00:00'))";
            $endExpr = "datetime(date(check_out_date) || ' ' || COALESCE(time(check_out_time),'23:59:59'))";
        } else {
            $startExpr = "STR_TO_DATE(CONCAT(DATE(check_in_date),' ',COALESCE(check_in_time,'00:00:00')), '%Y-%m-%d %H:%i:%s')";
            $endExpr = "STR_TO_DATE(CONCAT(DATE(check_out_date),' ',COALESCE(check_out_time,'23:59:59')), '%Y-%m-%d %H:%i:%s')";
        }

        return [$startExpr, $endExpr];
    }

    /** @return array<int, int> */
    public function bookedCountPerUnit(Carbon $checkIn, Carbon $checkOut): array
    {
        [$startExpr, $endExpr] = $this->overlapExpressions();
        $in = $checkIn->format('Y-m-d H:i:s');
        $out = $checkOut->format('Y-m-d H:i:s');
        $now = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        return Reservation::query()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereNotNull('unit_id')
            ->whereRaw("$endExpr > ?", [$now])
            ->whereRaw("$startExpr < ?", [$out])
            ->whereRaw("$endExpr > ?", [$in])
            ->selectRaw('unit_id, COUNT(*) as aggregate')
            ->groupBy('unit_id')
            ->pluck('aggregate', 'unit_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function remainingCapacity(int $unitId, Carbon $checkIn, Carbon $checkOut): int
    {
        $unit = Unit::query()->find($unitId);
        if ($unit === null) {
            return 0;
        }

        [$startExpr, $endExpr] = $this->overlapExpressions();
        $in = $checkIn->format('Y-m-d H:i:s');
        $out = $checkOut->format('Y-m-d H:i:s');
        $now = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $overlapping = Reservation::query()
            ->where('unit_id', $unit->id)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereRaw("$endExpr > ?", [$now])
            ->whereRaw("$startExpr < ?", [$out])
            ->whereRaw("$endExpr > ?", [$in])
            ->count();

        return max(0, (int) $unit->stock - $overlapping);
    }

    public function lockUnitForStay(int $unitId, Carbon $checkIn, Carbon $checkOut, ?int $excludeReservationId = null): Unit
    {
        $unit = Unit::query()->whereKey($unitId)->lockForUpdate()->firstOrFail();

        [$startExpr, $endExpr] = $this->overlapExpressions();
        $in = $checkIn->format('Y-m-d H:i:s');
        $out = $checkOut->format('Y-m-d H:i:s');
        $now = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $overlapping = Reservation::query()
            ->where('unit_id', $unit->id)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->when($excludeReservationId !== null, fn ($q) => $q->whereKeyNot($excludeReservationId))
            ->whereRaw("$endExpr > ?", [$now])
            ->whereRaw("$startExpr < ?", [$out])
            ->whereRaw("$endExpr > ?", [$in])
            ->lockForUpdate()
            ->count();

        if ($unit->stock - $overlapping <= 0) {
            throw new \RuntimeException('Unit/glamping sedang digunakan atau penuh pada jam tersebut.');
        }

        return $unit;
    }
}

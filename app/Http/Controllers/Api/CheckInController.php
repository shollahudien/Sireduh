<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class CheckInController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $today = now('Asia/Jakarta')->toDateString();
        $from = $validated['from'] ?? $today;
        $to = $validated['to'] ?? $today;

        $checkedInToday = Ticket::query()
            ->whereDate('used_at', $today)
            ->count();

        $daily = Ticket::query()
            ->whereNotNull('used_at')
            ->whereBetween(DB::raw('DATE(used_at)'), [$from, $to])
            ->selectRaw('DATE(used_at) as date, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(used_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->date,
                'total' => (int) $row->total,
            ])
            ->values();

        $totalScanned = (int) $daily->sum('total');

        return response()->json([
            'checked_in_today' => $checkedInToday,
            'total_scanned' => $totalScanned,
            'from' => $from,
            'to' => $to,
            'daily' => $daily,
        ]);
    }

    public function validateHash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hash' => ['required', 'string', 'size:64'],
        ]);

        $hash = $validated['hash'];
        $now = now();

        try {
            $result = DB::transaction(function () use ($hash, $now) {
                $ticket = Ticket::query()
                    ->where('unique_hash_sha256', $hash)
                    ->lockForUpdate()
                    ->first();

                if ($ticket === null) {
                    return ['ok' => null, 'ticket' => null, 'conflict' => false]; // Forged / nonexistent hash
                }

                // Authorization: hanya petugas check-in yang boleh memvalidasi tiket
                Gate::authorize('validate', $ticket);

                if ($ticket->is_used) {
                    return ['ok' => 'used', 'ticket' => $ticket, 'conflict' => false];
                }

                $reservation = $ticket->reservation()->with('unit')->lockForUpdate()->first();

                // Waktu mulai = saat scan; selesai = mulai + durasi paket (sistem). Cegah bentrok jika jam tsb masih terpakai.
                $actualIn = $now->copy()->timezone('Asia/Jakarta');
                $actualOut = null;
                if ($reservation && $reservation->package_duration_hours) {
                    $actualOut = $actualIn->copy()->addHours((int) $reservation->package_duration_hours);
                } elseif ($reservation) {
                    $actualOut = $reservation->checkOutAt();
                }

                if ($reservation && $reservation->unit_id && $actualOut) {
                    $availability = app(AvailabilityService::class);
                    try {
                        $availability->lockUnitForStay((int) $reservation->unit_id, $actualIn, $actualOut, (int) $reservation->id);
                    } catch (RuntimeException $e) {
                        return ['ok' => null, 'ticket' => $ticket, 'conflict' => true, 'message' => $e->getMessage()];
                    }
                }

                $ticket->update(['is_used' => true, 'used_at' => $now]);

                if ($reservation && $reservation->check_in_date) {
                    $updates = ['check_in_time' => $actualIn->format('H:i:s')];
                    if ($actualOut) {
                        $updates['check_out_date'] = $actualOut->toDateString();
                        $updates['check_out_time'] = $actualOut->format('H:i:s');
                    }
                    $reservation->update($updates);
                }

                return ['ok' => true, 'ticket' => $ticket->fresh(['reservation.visitor', 'reservation.unit']), 'conflict' => false];
            });
        } catch (\Throwable $e) {
            \Log::error('Gagal memproses check-in', ['hash' => $hash, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses check-in. Coba lagi.',
            ], 500);
        }

        if (! empty($result['conflict'])) {
            AuditLogger::log(
                'checkin.conflict',
                'Check-in konflik/bentrok: '.($result['ticket']?->reservation?->order_code ?? 'unknown'),
                $result['ticket'] ?? null,
                ['message' => $result['message'] ?? null, 'hash_present' => true],
                $request
            );

            return response()->json([
                'success' => false,
                'status' => 'conflict',
                'message' => $result['message'] ?? 'Unit/glamping sedang digunakan pada jam tersebut.',
            ], 409);
        }

        if ($result['ok'] === true) {
            /** @var Ticket $ticket */
            $ticket = $result['ticket'];
            $reservation = $ticket->reservation;

            \Log::info('Check-in tiket berhasil', [
                'ticket_id' => $ticket->id,
                'reservation_id' => $reservation?->id,
                'order_code' => $reservation?->order_code,
                'used_at' => $ticket->used_at?->toIso8601String(),
            ]);

            AuditLogger::log(
                'checkin.valid',
                'Check-in berhasil untuk '.($reservation?->order_code ?? ('tiket #'.$ticket->id)),
                $reservation ?: $ticket,
                [
                    'ticket_id' => $ticket->id,
                    'reservation_id' => $reservation?->id,
                    'order_code' => $reservation?->order_code,
                    'used_at' => $ticket->used_at?->toIso8601String(),
                ],
                $request,
                null,
                $reservation?->order_code
            );

            return response()->json([
                'success' => true,
                'status' => 'valid',
                'message' => 'Check-in berhasil. Tiket valid.',
                'ticket' => [
                    'id' => $ticket->id,
                    'is_used' => $ticket->is_used,
                    'used_at' => $ticket->used_at?->toIso8601String(),
                ],
                'reservation' => $reservation ? [
                    'id' => $reservation->id,
                    'order_code' => $reservation->order_code,
                    'visitor_name' => $reservation->visitor?->name,
                    'unit_name' => $reservation->unit?->name,
                    'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                    'check_in_time' => $reservation->check_in_time ? substr($reservation->check_in_time, 0, 5) : null,
                    'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                    'check_out_time' => $reservation->check_out_time ? substr($reservation->check_out_time, 0, 5) : null,
                ] : null,
            ]);
        }

        if ($result['ok'] === 'used') {
            $ticket = $result['ticket'];
            $reservation = $ticket?->reservation()->with(['visitor', 'unit'])->first();

            if ($ticket) {
                AuditLogger::log(
                    'checkin.used',
                    'Tiket sudah digunakan: '.($reservation?->order_code ?? ('tiket #'.$ticket->id)),
                    $reservation ?: $ticket,
                    [
                        'ticket_id' => $ticket->id,
                        'reservation_id' => $reservation?->id,
                        'order_code' => $reservation?->order_code,
                        'used_at' => $ticket->used_at?->toIso8601String(),
                    ],
                    $request,
                    null,
                    $reservation?->order_code
                );
            }

            return response()->json([
                'success' => false,
                'status' => 'used',
                'message' => 'Tiket sudah digunakan.',
                'ticket' => $ticket ? [
                    'id' => $ticket->id,
                    'is_used' => $ticket->is_used,
                    'used_at' => $ticket->used_at?->toIso8601String(),
                ] : null,
                'reservation' => $reservation ? [
                    'id' => $reservation->id,
                    'order_code' => $reservation->order_code,
                    'visitor_name' => $reservation->visitor?->name,
                    'unit_name' => $reservation->unit?->name,
                    'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                    'check_in_time' => $reservation->check_in_time ? substr($reservation->check_in_time, 0, 5) : null,
                    'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                    'check_out_time' => $reservation->check_out_time ? substr($reservation->check_out_time, 0, 5) : null,
                ] : null,
            ], 409);
        }

        return response()->json([
            'success' => false,
            'status' => 'invalid',
            'message' => 'Tiket tidak ditemukan atau tidak valid.',
        ], 404);
    }
}

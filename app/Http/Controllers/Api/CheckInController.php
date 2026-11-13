<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CheckInController extends Controller
{
    public function validateHash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hash' => ['required', 'string', 'size:64'],
        ]);

        $hash = $validated['hash'];
        $now = now();

        $result = DB::transaction(function () use ($hash, $now) {
            $ticket = Ticket::query()
                ->where('unique_hash_sha256', $hash)
                ->lockForUpdate()
                ->first();

            if ($ticket === null) {
                return ['ok' => null, 'ticket' => null]; // Forged / nonexistent hash
            }

            // Authorization: hanya petugas check-in yang boleh memvalidasi tiket
            Gate::authorize('validate', $ticket);

            if ($ticket->is_used) {
                return ['ok' => false, 'ticket' => $ticket];
            }

            $ticket->update(['is_used' => true, 'used_at' => $now]);

            return ['ok' => true, 'ticket' => $ticket->fresh(['reservation.visitor', 'reservation.unit'])];
        });

        if ($result['ok'] === true) {
            /** @var Ticket $ticket */
            $ticket = $result['ticket'];
            $reservation = $ticket->reservation;

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil.',
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
                    'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                ] : null,
            ]);
        }

        if ($result['ok'] === false) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket sudah digunakan atau tidak valid untuk check-in.',
            ], 409);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tiket tidak ditemukan.',
        ], 404);
    }
}

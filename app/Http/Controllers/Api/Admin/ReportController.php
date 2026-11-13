<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Unified revenue report combining online reservations and POS transactions.
     *
     * Query params: from, to (YYYY-MM-DD, default: bulan berjalan).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        $paidStatuses = ['paid', 'confirmed'];

        $reservations = Reservation::query()
            ->with(['visitor', 'unit'])
            ->whereIn('status', $paidStatuses)
            ->whereBetween(DB::raw('DATE(check_in_date)'), [$from, $to])
            ->latest('check_in_date')
            ->get();

        $posTransactions = PosTransaction::query()
            ->with(['user', 'details.product', 'details.unit'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->latest()
            ->get();

        $onlineRevenue = (float) $reservations->sum('total_price');
        $offlineRevenue = (float) $posTransactions->sum('total_amount');

        $transactions = collect()
            ->merge($reservations->map(fn (Reservation $reservation): array => [
                'source' => 'online',
                'reference' => $reservation->order_code,
                'customer' => $reservation->visitor?->name,
                'description' => $reservation->unit?->name ?? 'Tiket Masuk',
                'method' => 'transfer',
                'amount' => (float) $reservation->total_price,
                'date' => $reservation->check_in_date?->toDateString(),
            ]))
            ->merge($posTransactions->map(fn (PosTransaction $transaction): array => [
                'source' => 'pos',
                'reference' => 'POS-'.str_pad((string) $transaction->id, 5, '0', STR_PAD_LEFT),
                'customer' => $transaction->user?->name ?? 'Kasir',
                'description' => $transaction->details->map(fn ($detail) => $detail->quantity.'× '.($detail->product?->name ?? $detail->unit?->name))->implode(', '),
                'method' => $transaction->payment_method,
                'amount' => (float) $transaction->total_amount,
                'date' => $transaction->created_at?->toDateString(),
            ]))
            ->sortByDesc('date')
            ->values()
            ->all();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'summary' => [
                'online_revenue' => $onlineRevenue,
                'offline_revenue' => $offlineRevenue,
                'total_revenue' => $onlineRevenue + $offlineRevenue,
                'online_transactions' => $reservations->count(),
                'pos_transactions' => $posTransactions->count(),
                'total_transactions' => $reservations->count() + $posTransactions->count(),
            ],
            'transactions' => $transactions,
        ]);
    }
}

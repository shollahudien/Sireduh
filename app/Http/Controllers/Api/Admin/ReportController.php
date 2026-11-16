<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Reservation;
use Carbon\Carbon;
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
            ->with(['visitor', 'unit', 'payment'])
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
            ->merge($reservations->map(function (Reservation $reservation): array {
                $payment = $reservation->payment;
                $method = $payment?->payment_type ?: ($payment?->method === 'gateway' ? ($payment?->provider ?: 'gateway') : 'transfer');

                return [
                    'source' => 'online',
                    'reference' => $reservation->order_code,
                    'customer' => $reservation->visitor?->name,
                    'description' => $reservation->unit?->name ?? 'Tiket Masuk',
                    'method' => $method,
                    'provider' => $payment?->provider,
                    'provider_ref' => $payment?->provider_order_id,
                    'payment_status' => $payment?->status,
                    'amount' => (float) $reservation->total_price,
                    'date' => $reservation->check_in_date?->toDateString(),
                ];
            }))
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

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $days = max(1, $start->diffInDays($end) + 1);
        $labels = [];
        $dailyOnline = array_fill(0, $days, 0.0);
        $dailyOffline = array_fill(0, $days, 0.0);

        for ($i = 0; $i < $days; $i++) {
            $labels[] = $start->copy()->addDays($i)->format('d M');
        }

        foreach ($reservations as $reservation) {
            $date = $reservation->check_in_date?->copy()->startOfDay();
            if (! $date) {
                continue;
            }
            $index = $start->diffInDays($date);
            if ($index >= 0 && $index < $days) {
                $dailyOnline[$index] += (float) $reservation->total_price;
            }
        }

        $offlineByMethod = [
            'cash' => ['method' => 'cash', 'label' => 'Tunai', 'transactions' => 0, 'total_amount' => 0.0],
            'qris' => ['method' => 'qris', 'label' => 'QRIS', 'transactions' => 0, 'total_amount' => 0.0],
            'transfer' => ['method' => 'transfer', 'label' => 'Transfer', 'transactions' => 0, 'total_amount' => 0.0],
        ];
        $offlineByCashier = [];

        foreach ($posTransactions as $transaction) {
            $date = $transaction->created_at?->copy()->startOfDay();
            if ($date) {
                $index = $start->diffInDays($date);
                if ($index >= 0 && $index < $days) {
                    $dailyOffline[$index] += (float) $transaction->total_amount;
                }
            }

            $method = $transaction->payment_method ?: 'other';
            if (! isset($offlineByMethod[$method])) {
                $offlineByMethod[$method] = ['method' => $method, 'label' => strtoupper($method), 'transactions' => 0, 'total_amount' => 0.0];
            }
            $offlineByMethod[$method]['transactions']++;
            $offlineByMethod[$method]['total_amount'] += (float) $transaction->total_amount;

            $key = (string) $transaction->user_id;
            if (! isset($offlineByCashier[$key])) {
                $offlineByCashier[$key] = [
                    'user_id' => $transaction->user_id,
                    'name' => $transaction->user?->name ?? 'Kasir #'.$transaction->user_id,
                    'role' => $transaction->user?->role ?? '-',
                    'transactions' => 0,
                    'total_amount' => 0.0,
                ];
            }
            $offlineByCashier[$key]['transactions']++;
            $offlineByCashier[$key]['total_amount'] += (float) $transaction->total_amount;
        }

        $offlineByCashier = array_values($offlineByCashier);
        usort($offlineByCashier, fn ($a, $b) => $b['total_amount'] <=> $a['total_amount']);

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
            'daily' => [
                'labels' => $labels,
                'online_revenue' => $dailyOnline,
                'offline_revenue' => $dailyOffline,
            ],
            'offline_by_method' => array_values($offlineByMethod),
            'offline_by_cashier' => $offlineByCashier,
            'transactions' => $transactions,
        ]);
    }
}

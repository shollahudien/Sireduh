<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function analytics(Request $request)
    {
        $days = (int) $request->input('days', 30);
        if ($days < 7) {
            $days = 7;
        }
        if ($days > 90) {
            $days = 90;
        }

        $start = now()->subDays($days - 1)->startOfDay();

        $reservations = Reservation::where('created_at', '>=', $start)->get(['created_at', 'total_price', 'status']);
        $posTransactions = PosTransaction::with('user:id,name,role')
            ->where('created_at', '>=', $start)
            ->get(['id', 'created_at', 'total_amount', 'user_id', 'payment_method']);

        $labels = [];
        $visitors = array_fill(0, $days, 0);
        $onlineRevenue = array_fill(0, $days, 0);
        $offlineRevenue = array_fill(0, $days, 0);
        $sales = array_fill(0, $days, 0);

        for ($i = 0; $i < $days; $i++) {
            $labels[] = $start->copy()->addDays($i)->format('d M');
        }

        foreach ($reservations as $reservation) {
            $index = $start->diffInDays($reservation->created_at->copy()->startOfDay());
            if ($index < 0 || $index >= $days) {
                continue;
            }
            $visitors[$index]++;
            $sales[$index]++;
            if ($reservation->status !== 'cancelled') {
                $onlineRevenue[$index] += (float) $reservation->total_price;
            }
        }

        $offlineByCashier = [];
        $offlineByMethod = [
            'cash' => ['method' => 'cash', 'label' => 'Tunai', 'transactions' => 0, 'total_amount' => 0.0],
            'qris' => ['method' => 'qris', 'label' => 'QRIS', 'transactions' => 0, 'total_amount' => 0.0],
            'transfer' => ['method' => 'transfer', 'label' => 'Transfer', 'transactions' => 0, 'total_amount' => 0.0],
        ];

        foreach ($posTransactions as $transaction) {
            $index = $start->diffInDays($transaction->created_at->copy()->startOfDay());
            if ($index < 0 || $index >= $days) {
                continue;
            }
            $amount = (float) $transaction->total_amount;
            $offlineRevenue[$index] += $amount;
            $sales[$index]++;

            $method = $transaction->payment_method ?: 'other';
            if (! isset($offlineByMethod[$method])) {
                $offlineByMethod[$method] = ['method' => $method, 'label' => strtoupper($method), 'transactions' => 0, 'total_amount' => 0.0];
            }
            $offlineByMethod[$method]['transactions']++;
            $offlineByMethod[$method]['total_amount'] += $amount;

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
            $offlineByCashier[$key]['total_amount'] += $amount;
        }

        $offlineByCashier = array_values($offlineByCashier);
        usort($offlineByCashier, fn ($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        $onlineTotal = array_sum($onlineRevenue);
        $offlineTotal = array_sum($offlineRevenue);

        return response()->json([
            'days' => $days,
            'labels' => $labels,
            'visitors' => $visitors,
            'online_revenue' => $onlineRevenue,
            'offline_revenue' => $offlineRevenue,
            'sales' => $sales,
            'offline_by_cashier' => $offlineByCashier,
            'offline_by_method' => array_values($offlineByMethod),
            'totals' => [
                'visitors' => array_sum($visitors),
                'online_revenue' => $onlineTotal,
                'offline_revenue' => $offlineTotal,
                'total_revenue' => $onlineTotal + $offlineTotal,
                'sales' => array_sum($sales),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PosTransaction;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Unified dashboard statistics for SIRESTEDUH admin.
     *
     * - revenue online: reservations berstatus paid/confirmed
     * - revenue offline: POS transactions
     * - visitors_today: tiket terpakai (scan gate) hari ini
     */
    public function stats(): JsonResponse
    {
        $paidStatuses = ['paid', 'confirmed'];

        $onlineRevenue = (float) Reservation::query()
            ->whereIn('status', $paidStatuses)
            ->sum('total_price');

        $offlineRevenue = (float) PosTransaction::query()
            ->sum('total_amount');

        $pendingReservations = Reservation::query()
            ->where('status', 'waiting_verification')
            ->count();

        $pendingPayments = Payment::query()
            ->where('status', 'pending')
            ->count();

        $visitorsToday = Ticket::query()
            ->where('is_used', true)
            ->whereDate('used_at', today())
            ->count();

        $revenueSeries = collect(range(6, 0))->map(function (int $daysAgo) use ($paidStatuses): array {
            $date = now()->subDays($daysAgo)->startOfDay();

            $online = (float) Reservation::query()
                ->whereIn('status', $paidStatuses)
                ->whereDate('check_in_date', $date->toDateString())
                ->sum('total_price');

            $offline = (float) PosTransaction::query()
                ->whereDate('created_at', $date->toDateString())
                ->sum('total_amount');

            return [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('d M'),
                'online' => $online,
                'offline' => $offline,
            ];
        })->values()->all();

        return response()->json([
            'total_revenue' => $onlineRevenue + $offlineRevenue,
            'online_revenue' => $onlineRevenue,
            'offline_revenue' => $offlineRevenue,
            'pending_reservations' => $pendingReservations,
            'pending_payments' => $pendingPayments,
            'visitors_today' => $visitorsToday,
            'total_reservations' => Reservation::query()->count(),
            'revenue_series' => $revenueSeries,
        ]);
    }
}

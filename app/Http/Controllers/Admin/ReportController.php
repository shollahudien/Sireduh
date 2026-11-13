<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * JSON endpoint untuk dashboard laporan (Alpine.js fetch)
     */
    public function data(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $reservations = Reservation::whereIn('status', ['paid', 'confirmed', 'checked_in', 'checked_out'])
            ->whereBetween(DB::raw('DATE(check_in_date)'), [$from, $to]);

        $posTotal = PosTransaction::whereBetween(
            DB::raw('DATE(created_at)'), [$from, $to]
        )->sum('total_amount');

        // Per-hari
        $daily = Reservation::whereIn('status', ['paid', 'confirmed', 'checked_in', 'checked_out'])
            ->whereBetween(DB::raw('DATE(check_in_date)'), [$from, $to])
            ->selectRaw('DATE(check_in_date) as date, COUNT(*) as count, SUM(total_price) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'count' => $r->count,
                'revenue' => $r->revenue,
            ]);

        return response()->json([
            'total_reservations' => $reservations->count(),
            'confirmed' => $reservations->clone()->whereIn('status', ['confirmed'])->count(),
            'pos_transactions' => PosTransaction::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->count(),
            'total_revenue' => (float) $reservations->sum('total_price') + (float) $posTotal,
            'daily' => $daily,
        ]);
    }
}

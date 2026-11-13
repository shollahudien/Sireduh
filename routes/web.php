<?php

use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProofController as AdminProofController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Api\Admin\LogController as AdminLogController;
use App\Http\Controllers\GuestETicketController;
use App\Http\Controllers\GuestStatusController;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\LogPruneService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------------------
// Public
// -------------------------------------------------------------------------
Route::get('/', fn () => view('home'))->name('home');
Route::get('/checkout', fn () => view('public.checkout'))->name('checkout');
Route::get('/pembayaran/{orderCode}', fn (string $orderCode) => view('public.payment', ['orderCode' => $orderCode]))->name('payments.show');
Route::redirect('/tiket', '/checkout')->name('ticket.index');

// Authentication
require __DIR__.'/auth.php';

// -------------------------------------------------------------------------
// Admin
// -------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

    // Reservasi
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::put('/reservations/{reservation}/verify', [ReservationController::class, 'verify'])->name('reservations.verify');
    Route::put('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Verifikasi pembayaran — route by reservation_id agar konsisten dengan API
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::put('/payments/{reservation}', [PaymentController::class, 'verifyByReservation'])->name('payments.verify');

    // Halaman admin
    Route::get('/verification', fn () => view('admin.verification'))->name('verification');
    Route::get('/units', fn () => view('admin.units'))->name('units');
    Route::get('/products', fn () => view('admin.products'))->name('products');
    Route::get('/users', fn () => view('admin.users'))->name('users');
    Route::get('/maintenance', fn () => view('admin.maintenance'))->name('maintenance');
    Route::get('/logs', function () {
        // Lazy prune (sekali per jam) — jaga-jaga bila scheduler/cron tidak berjalan.
        $days = (int) Setting::get('log_retention_days', '30');
        if ($days > 0 && ! Cache::get('logs_pruned_at')) {
            (new LogPruneService)->prune($days);
            Cache::put('logs_pruned_at', now(), now()->addHour());
        }

        return view('admin.logs');
    })->name('logs');
    Route::get('/activity', function () {
        // Lazy prune (sekali per jam) — jaga-jaga bila scheduler/cron tidak berjalan.
        $days = (int) Setting::get('activity_retention_days', '30');
        if ($days > 0 && ! Cache::get('activity_pruned_at')) {
            ActivityLog::pruneOlderThan($days);
            Cache::put('activity_pruned_at', now(), now()->addHour());
        }

        return view('admin.activity');
    })->name('activity');
    Route::get('/logs/download', [AdminLogController::class, 'download'])->name('logs.download');

    // Laporan
    Route::get('/reports', fn () => view('admin.reports'))->name('reports.index');
    Route::get('/reports/data', [ReportController::class, 'data'])->name('reports.data');

    // Check-in (admin view)
    Route::get('/checkin', [CheckInController::class, 'index'])->name('checkin.index');

    // Bukti pembayaran (private disk, hanya role admin)
    Route::get('/proofs/{reservation}', [AdminProofController::class, 'show'])->name('proofs.show');
});

// -------------------------------------------------------------------------
// POS Officer (kasir utama)
Route::middleware(['auth', 'role:kasir'])->name('pos.officer.')->group(function () {
    Route::get('/pos', fn () => view('pos.index'))->name('index');
    Route::get('/riwayat', fn () => view('pos.riwayat'))->name('riwayat');
});

// Kasir Makanan (F&B) — menjual HANYA produk kategori makanan
Route::middleware(['auth', 'role:kasir_makanan'])->name('kasir.makanan.')->group(function () {
    Route::get('/kasir-makanan', fn () => view('pos.index', ['foodOnly' => true]))->name('index');
    Route::get('/riwayat-makanan', fn () => view('pos.riwayat'))->name('riwayat');
});

// -------------------------------------------------------------------------
// Check-in Officer
// -------------------------------------------------------------------------
Route::middleware(['auth', 'role:checkin'])->name('checkin.officer.')->group(function () {
    Route::get('/checkin', fn () => view('checkin.scanner'))->name('index');
    Route::get('/checkin/tiket', fn () => view('checkin.tiket'))->name('tiket');
    Route::get('/riwayat-checkin', fn () => view('checkin.riwayat'))->name('riwayat');
});

// -------------------------------------------------------------------------
// Guest — Order Status
// -------------------------------------------------------------------------
Route::get('/status', fn () => view('guest.status-search'))->name('status.search');
Route::post('/status', [GuestStatusController::class, '__invoke'])->name('status.show');
Route::get('/e-ticket/{hash}/qr.png', GuestETicketController::class)
    ->name('e-ticket.qr')
    ->where('hash', '[a-f0-9]{64}');

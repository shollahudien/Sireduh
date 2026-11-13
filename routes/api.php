<?php

use App\Http\Controllers\Api\Admin\ActivityController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ETicketSendController;
use App\Http\Controllers\Api\Admin\GlobalSearchController;
use App\Http\Controllers\Api\Admin\ImageController;
use App\Http\Controllers\Api\Admin\LogController;
use App\Http\Controllers\Api\Admin\MaintenanceController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Api\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatbotSpeakController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\PaymentUploadController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

// Rate limiting via middleware inline (60/mnt public API, 10/mnt check-in) sesuai kebutuhan.

// Module 0 — External API auth (Sanctum Bearer tokens)
Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/auth/login', [TokenController::class, 'login'])->name('api.auth.login');
});
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [TokenController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [TokenController::class, 'logout'])->name('api.auth.logout');
});

// Module 1 — Public availability (rate limited)
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/units', [UnitController::class, 'index'])->name('api.units.index');
    Route::get('/tickets', [UnitController::class, 'tickets'])->name('api.tickets.index');
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('api.availability.index');
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('api.reservations.store');
    Route::post('/chat', [ChatbotController::class, 'ask'])
        ->name('api.chatbot.ask')
        ->middleware('throttle:15,1');

    // Suara AI untuk Teduh Bot (Gemini TTS) — lebih ketat karena mahal kuota
    Route::post('/chat/speak', [ChatbotSpeakController::class, 'speak'])
        ->name('api.chatbot.speak')
        ->middleware('throttle:10,1');
    Route::get('/reservations/{orderCode}/payment', [PaymentUploadController::class, 'show'])
        ->name('api.payments.show');
    Route::post('/payments/upload', [PaymentUploadController::class, 'store'])->name('api.payments.upload');
});

// Module 7 — Admin API
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function (): void {
    // Dashboard & stats
    Route::get('/dashboard-stats', [DashboardController::class, 'stats'])->name('api.admin.dashboard-stats');
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('api.admin.notifications');

    // Global search (command palette admin)
    Route::get('/search', [GlobalSearchController::class, 'index'])->name('api.admin.search');

    // Payment verification
    Route::get('/reservations/pending', [AdminReservationController::class, 'pending'])->name('api.admin.reservations.pending');
    Route::match(['post', 'put'], '/verify/{id}', [AdminPaymentController::class, 'verify'])->name('api.admin.verify');

    // Reports (unified)
    Route::get('/reports', [ReportController::class, 'index'])->name('api.admin.reports.index');

    // E-ticket (manual send / re-send)
    Route::post('/eticket/send/{reservation}', [ETicketSendController::class, 'send'])->name('api.admin.eticket.send');

    // Maintenance mode toggle
    Route::get('/maintenance', [MaintenanceController::class, 'show'])->name('api.admin.maintenance.show');
    Route::put('/maintenance', [MaintenanceController::class, 'update'])->name('api.admin.maintenance.update');

    // Log viewer
    Route::get('/logs', [LogController::class, 'files'])->name('api.admin.logs.files');
    Route::get('/logs/view', [LogController::class, 'view'])->name('api.admin.logs.view');
    Route::post('/logs/clear', [LogController::class, 'clear'])->name('api.admin.logs.clear');
    Route::put('/logs/retention', [LogController::class, 'updateRetention'])->name('api.admin.logs.retention');

    // Activity feed
    Route::get('/activity', [ActivityController::class, 'index'])->name('api.admin.activity.index');
    Route::post('/activity/clear', [ActivityController::class, 'clear'])->name('api.admin.activity.clear');
    Route::put('/activity/retention', [ActivityController::class, 'updateRetention'])->name('api.admin.activity.retention');

    // Master data — units
    Route::get('/units', [AdminUnitController::class, 'index'])->name('api.admin.units.index');
    Route::post('/units', [AdminUnitController::class, 'store'])->name('api.admin.units.store');
    Route::get('/units/{unit}', [AdminUnitController::class, 'show'])->name('api.admin.units.show');
    Route::put('/units/{unit}', [AdminUnitController::class, 'update'])->name('api.admin.units.update');
    Route::delete('/units/{unit}', [AdminUnitController::class, 'destroy'])->name('api.admin.units.destroy');
    Route::patch('/units/{unit}/stock', [AdminUnitController::class, 'stock'])->name('api.admin.units.stock');
    Route::post('/units/{unit}/images', [ImageController::class, 'storeUnit'])->name('api.admin.units.images.store');
    Route::delete('/units/{unit}/images/{image}', [ImageController::class, 'destroyUnitImage'])->name('api.admin.units.images.destroy');

    // Master data — products
    Route::get('/products', [AdminProductController::class, 'index'])->name('api.admin.products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('api.admin.products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('api.admin.products.show');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('api.admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('api.admin.products.destroy');
    Route::patch('/products/{product}/stock', [AdminProductController::class, 'stock'])->name('api.admin.products.stock');
    Route::post('/products/{product}/images', [ImageController::class, 'storeProduct'])->name('api.admin.products.images.store');
    Route::delete('/products/{product}/images/{image}', [ImageController::class, 'destroyProductImage'])->name('api.admin.products.images.destroy');

    // Staff user management
    Route::get('/users', [UserController::class, 'index'])->name('api.admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('api.admin.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('api.admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('api.admin.users.destroy');
    Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])->name('api.admin.users.password');
});

// The admin CRUD API uses the application's session-based admin guard. Sanctum can
// be substituted here when API tokens are enabled for the deployment.

// POS Officer (kasir & kasir makanan & kasir tiket) — termasuk riwayat pribadi
Route::middleware(['auth', 'role:kasir,kasir_makanan,checkin'])->prefix('pos')->group(function (): void {
    Route::get('/products', [PosController::class, 'products'])->name('api.pos.products');
    Route::get('/units', [PosController::class, 'units'])->name('api.pos.units');
    Route::get('/riwayat', [PosController::class, 'riwayat'])->name('api.pos.riwayat');
    Route::post('/checkout', [PosController::class, 'checkout'])->name('api.pos.checkout');
});

// Module 6 — QR check-in (rate limited: brute-force protection)
Route::middleware(['auth', 'role:checkin', 'throttle:10,1'])->prefix('checkin')->group(function (): void {
    Route::post('/validate', [CheckInController::class, 'validateHash'])->name('api.checkin.validate');
});

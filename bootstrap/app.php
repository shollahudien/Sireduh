<?php

use App\Http\Middleware\EnsureNotUnderMaintenance;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Blade + Alpine memanggil API dari origin yang sama; session cookie
        // (SameSite=Lax) dipakai untuk autentikasi API berbasis role.
        $middleware->api(prepend: [
            EncryptCookies::class,
            StartSession::class,
        ]);

        // Mode maintenance: hanya admin yang boleh akses saat diaktifkan.
        // Append (bukan prepend) agar berjalan SETELAH session dimuat.
        $middleware->web(append: [
            EnsureNotUnderMaintenance::class,
            LogActivity::class,
        ]);

        $middleware->api(append: [
            EnsureNotUnderMaintenance::class,
            LogActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Reservasi pending tanpa pembayaran tidak boleh menahan stok selamanya.
        $schedule->command('reservations:expire-pending')->everyMinute();
        $schedule->command('activity:prune')->dailyAt('03:00');
        $schedule->command('logs:prune')->dailyAt('03:05');
        $schedule->command('sanctum:prune-expired --hours=24')->dailyAt('03:10');
        $schedule->command('cache:prune-expired')->dailyAt('03:15');
        $schedule->command('cache:clear')->cron('0 3 */3 * *');
    })->create();

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('reservations-store', function (Request $request): array {
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(3)->by($ip)->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak percobaan pemesanan dari perangkat/jaringan ini. Tunggu sebentar lalu coba lagi.',
                    ], 429);
                }),
                Limit::perHour(15)->by($ip)->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Batas pemesanan dari perangkat/jaringan ini tercapai. Silakan coba lagi nanti.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));
            $key = $email.'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return back()->withErrors([
                    'email' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa saat.',
                ])->onlyInput('email');
            });
        });
    }
}

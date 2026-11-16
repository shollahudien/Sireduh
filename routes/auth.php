<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/login', fn () => view('auth.login'))->name('login');

Route::middleware('throttle:login')->post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email:rfc', 'max:255'],
        'password' => ['required', 'string', 'max:255'],
    ]);

    $attemptedEmail = Str::lower(trim((string) $credentials['email']));

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if (Setting::maintenanceEnabled() && $user->role !== User::ROLE_ADMIN) {
            AuditLogger::log(
                'auth.login.blocked',
                'Login ditolak: maintenance aktif untuk non-admin.',
                $user,
                ['email' => $attemptedEmail, 'reason' => 'maintenance'],
                $request
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'SIRESTEDUH sedang dalam perbaikan. Hanya admin yang dapat masuk saat ini.',
            ])->onlyInput('email');
        }

        AuditLogger::log(
            'auth.login.success',
            'Login berhasil: '.$user->name.' ('.$user->role.').',
            $user,
            ['email' => $user->email, 'role' => $user->role],
            $request
        );

        if ($user->role === User::ROLE_ADMIN) {
            return redirect()->intended('/admin');
        }

        if ($user->role === User::ROLE_KASIR) {
            return redirect()->intended('/pos');
        }

        if ($user->role === User::ROLE_KASIR_MAKANAN) {
            return redirect()->intended('/kasir-makanan');
        }

        if ($user->role === User::ROLE_CHECKIN) {
            return redirect()->intended('/checkin');
        }

        return redirect()->intended('/');
    }

    AuditLogger::log(
        'auth.login.failed',
        'Login gagal: kredensial tidak valid.',
        null,
        ['email' => $attemptedEmail, 'reason' => 'invalid_credentials'],
        $request
    );

    return back()->withErrors([
        'email' => 'Email atau password salah.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    $user = $request->user();
    AuditLogger::log('auth.logout', 'Logout.', $user, ['email' => $user?->email], $request);

    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/logout', function (Request $request) {
    $user = $request->user();
    AuditLogger::log('auth.logout', 'Logout.', $user, ['email' => $user?->email], $request);

    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
});

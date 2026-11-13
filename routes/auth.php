<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => view('auth.login'))->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if (Setting::maintenanceEnabled() && $user->role !== User::ROLE_ADMIN) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'SIRESTEDUH sedang dalam perbaikan. Hanya admin yang dapat masuk saat ini.',
            ])->onlyInput('email');
        }

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

    return back()->withErrors([
        'email' => 'Email atau password salah.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Support GET logout as well for convenience
Route::get('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
});

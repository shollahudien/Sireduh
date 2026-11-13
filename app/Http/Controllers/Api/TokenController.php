<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * External API login → issue a Sanctum personal access token (Bearer).
     * Dipakai untuk client non-browser (mobile app). Halaman Blade/Alpine
     * tetap memakai sesi cookie seperti biasa.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken(
            $validated['device_name'] ?? 'api-client',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 1440)),
        );

        return response()->json([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_in' => (int) config('sanctum.expiration', 1440) * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Info user yang sedang login via Bearer token (atau sesi cookie).
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Cabut token yang sedang dipakai (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil dicabut.',
        ]);
    }
}

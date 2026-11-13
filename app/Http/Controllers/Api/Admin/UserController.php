<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UserStoreRequest;
use App\Http\Requests\Api\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    /**
     * List all staff users (never includes the password hash).
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderByRaw("case role when 'admin' then 1 when 'kasir' then 2 else 3 end")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at', 'updated_at']);

        return response()->json(['data' => $users]);
    }

    /**
     * Create a new staff account.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'data' => $this->present($user),
        ], 201);
    }

    /**
     * Update name/email/role of a staff account.
     * The acting admin cannot change their own role (would lock themselves out).
     */
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if ($request->user()->is($user) && $validated['role'] !== $user->role) {
            return response()->json([
                'message' => 'Anda tidak bisa mengubah role akun Anda sendiri.',
            ], 422);
        }

        if ($user->role === 'admin' && $validated['role'] !== 'admin' && $this->adminCount() <= 1) {
            return response()->json([
                'message' => 'Minimal harus ada 1 akun admin. Role admin terakhir tidak bisa diturunkan.',
            ], 422);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $this->present($user->fresh()),
        ]);
    }

    /**
     * Reset a staff account password (admin action, no current password required).
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'message' => 'Password berhasil direset.',
        ]);
    }

    /**
     * Delete a staff account.
     * - cannot delete yourself
     * - cannot delete the last remaining admin
     * - cannot delete a user who already recorded POS transactions (FK restrict)
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'Anda tidak bisa menghapus akun Anda sendiri.',
            ], 422);
        }

        if ($user->role === 'admin' && $this->adminCount() <= 1) {
            return response()->json([
                'message' => 'Minimal harus ada 1 akun admin. Admin terakhir tidak bisa dihapus.',
            ], 422);
        }

        try {
            $user->delete();
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Pengguna tidak bisa dihapus karena memiliki transaksi POS tercatat.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * @return array{id:int, name:string, email:string, role:string, created_at:string|null, updated_at:string|null}
     */
    protected function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    protected function adminCount(): int
    {
        return User::query()->where('role', 'admin')->count();
    }
}

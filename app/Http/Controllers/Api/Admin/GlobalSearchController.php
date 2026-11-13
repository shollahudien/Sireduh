<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pencarian global untuk command palette admin.
 *
 * Satu endpoint, beberapa grup: reservasi, unit sewa, produk,
 * pengguna, pembayaran tertunda, dan log aktivitas.
 */
class GlobalSearchController extends Controller
{
    private const LIMIT_PER_GROUP = 5;

    private const STATUS_LABELS = [
        'pending' => 'Menunggu Pembayaran',
        'waiting_verification' => 'Menunggu Verifikasi',
        'paid' => 'Lunas',
        'confirmed' => 'Terverifikasi',
        'cancelled' => 'Dibatalkan',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:50'],
        ]);

        $term = trim($validated['q']);
        $needle = '%'.addcslashes($term, '\\%_').'%';

        $groups = array_values(array_filter([
            $this->searchReservations($needle),
            $this->searchUnits($needle),
            $this->searchProducts($needle),
            $this->searchUsers($needle),
            $this->searchPayments($needle),
            $this->searchActivity($needle),
        ], fn (array $group): bool => $group['items'] !== []));

        return response()->json([
            'query' => $term,
            'groups' => $groups,
            'total' => array_sum(array_map(
                fn (array $group): int => count($group['items']),
                $groups
            )),
        ]);
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchReservations(string $needle): array
    {
        $rows = Reservation::query()
            ->where(fn ($q) => $q
                ->where('order_code', 'like', $needle)
                ->orWhereHas('visitor', fn ($v) => $v
                    ->where('name', 'like', $needle)
                    ->orWhere('whatsapp_number', 'like', $needle)))
            ->with('visitor')
            ->orderByDesc('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'reservations',
            'label' => 'Reservasi',
            'icon' => '📋',
            'items' => $rows->map(fn (Reservation $r): array => [
                'title' => $r->order_code,
                'subtitle' => $r->visitor?->name.' · '.(self::STATUS_LABELS[$r->status] ?? $r->status),
                'url' => route('admin.reservations.index'),
            ])->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchUnits(string $needle): array
    {
        $rows = Unit::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', $needle)
                ->orWhere('type', 'like', $needle))
            ->orderBy('name')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'units',
            'label' => 'Unit Sewa',
            'icon' => '🏕️',
            'items' => $rows->map(fn (Unit $u): array => [
                'title' => $u->name,
                'subtitle' => ucfirst($u->type).' · Rp '.number_format((float) $u->price, 0, ',', '.'),
                'url' => route('admin.units'),
            ])->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchProducts(string $needle): array
    {
        $rows = Product::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', $needle)
                ->orWhere('category', 'like', $needle))
            ->orderBy('name')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'products',
            'label' => 'Produk',
            'icon' => '🛍️',
            'items' => $rows->map(fn (Product $p): array => [
                'title' => $p->name,
                'subtitle' => ucfirst($p->category).' · Rp '.number_format((float) $p->price, 0, ',', '.'),
                'url' => route('admin.products'),
            ])->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchUsers(string $needle): array
    {
        $rows = User::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', $needle)
                ->orWhere('email', 'like', $needle))
            ->orderBy('name')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'users',
            'label' => 'Pengguna',
            'icon' => '👥',
            'items' => $rows->map(fn (User $u): array => [
                'title' => $u->name,
                'subtitle' => $u->email.' · '.ucfirst($u->role),
                'url' => route('admin.users'),
            ])->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchPayments(string $needle): array
    {
        $rows = Payment::query()
            ->where('status', 'pending')
            ->whereHas('reservation', fn ($r) => $r
                ->where('order_code', 'like', $needle)
                ->orWhereHas('visitor', fn ($v) => $v
                    ->where('name', 'like', $needle)
                    ->orWhere('whatsapp_number', 'like', $needle)))
            ->with('reservation.visitor')
            ->orderByDesc('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'payments',
            'label' => 'Pembayaran Menunggu',
            'icon' => '💳',
            'items' => $rows->map(fn (Payment $p): array => [
                'title' => $p->reservation?->order_code ?? 'Pembayaran',
                'subtitle' => $p->reservation?->visitor?->name ?? 'Menunggu verifikasi',
                'url' => route('admin.verification'),
            ])->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function searchActivity(string $needle): array
    {
        $rows = ActivityLog::query()
            ->where(fn ($q) => $q
                ->where('user_name', 'like', $needle)
                ->orWhere('path', 'like', $needle))
            ->orderByDesc('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        return [
            'key' => 'activity',
            'label' => 'Log Aktivitas',
            'icon' => '🗂️',
            'items' => $rows->map(fn (ActivityLog $log): array => [
                'title' => $log->user_name.' · '.$log->method.' '.$log->path,
                'subtitle' => 'Status '.$log->status.' · '.$log->created_at->translatedFormat('d M Y H:i'),
                'url' => route('admin.activity'),
            ])->all(),
        ];
    }
}

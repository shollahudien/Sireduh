@extends('layouts.admin')
@section('page-title', 'Daftar Reservasi')

@section('content')
@php
    $statusMeta = function (string $status): array {
        return match ($status) {
            'paid', 'confirmed', 'checked_in' => ['label' => str_replace('_', ' ', $status), 'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
            'pending', 'waiting_verification' => ['label' => str_replace('_', ' ', $status), 'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
            'cancelled', 'expired' => ['label' => str_replace('_', ' ', $status), 'class' => 'bg-rose-100 text-rose-700 border-rose-200'],
            'checked_out' => ['label' => str_replace('_', ' ', $status), 'class' => 'bg-indigo-100 text-indigo-700 border-indigo-200'],
            default => ['label' => str_replace('_', ' ', $status), 'class' => 'bg-gray-100 text-gray-600 border-gray-200'],
        };
    };
@endphp

<div x-data="reservationPage()" class="space-y-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-bold text-gray-800 text-base sm:text-lg">Semua Reservasi</h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola dan pantau reservasi tamu</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" id="searchInput" x-model="query" @input="filterReservations()"
                           placeholder="Cari kode / nama / WA / unit..."
                           class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-green-400 focus:border-green-400 transition">
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 text-blue-800 px-4 py-3 text-sm font-medium">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[920px]" id="reservationTable">
                    <thead class="bg-gray-50/80 sticky top-0 z-[1]">
                        <tr class="text-left text-gray-500 border-b border-gray-100 text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 font-bold">Kode</th>
                            <th class="px-4 py-3 font-bold">Tamu</th>
                            <th class="px-4 py-3 font-bold">WhatsApp</th>
                            <th class="px-4 py-3 font-bold">Unit / Paket</th>
                            <th class="px-4 py-3 font-bold">Check-in</th>
                            <th class="px-4 py-3 font-bold">Check-out</th>
                            <th class="px-4 py-3 font-bold text-right">Total</th>
                            <th class="px-4 py-3 font-bold text-center">Status</th>
                            <th class="px-4 py-3 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reservations as $r)
                            @php
                                $meta = $statusMeta($r->status);
                                $checkIn = trim(($r->check_in_date ?? '-') . ' ' . ($r->check_in_time ? \Illuminate\Support\Str::substr($r->check_in_time, 0, 5) : ''));
                                $checkOut = trim(($r->check_out_date ?? '-') . ' ' . ($r->check_out_time ? \Illuminate\Support\Str::substr($r->check_out_time, 0, 5) : ''));
                                $searchText = strtolower(implode(' ', array_filter([
                                    $r->order_code,
                                    $r->visitor->name ?? '',
                                    $r->visitor->whatsapp_number ?? '',
                                    $r->unit->name ?? '',
                                    $r->status,
                                    $checkIn,
                                    $checkOut,
                                ])));
                                $detail = [
                                    'id' => $r->id,
                                    'order_code' => $r->order_code,
                                    'guest_name' => $r->visitor->name ?? '-',
                                    'guest_wa' => $r->visitor->whatsapp_number ?? '-',
                                    'unit' => $r->unit->name ?? '-',
                                    'check_in' => $checkIn,
                                    'check_out' => $checkOut,
                                    'total' => 'Rp ' . number_format((float) $r->total_price, 0, ',', '.'),
                                    'status' => $r->status,
                                    'status_label' => $meta['label'],
                                    'status_class' => $meta['class'],
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/80 align-top" data-reservation-item data-search="{{ e($searchText) }}">
                                <td class="px-4 py-3 font-mono font-semibold text-xs text-gray-800 whitespace-nowrap">{{ $r->order_code }}</td>
                                <td class="px-4 py-3 text-gray-800 min-w-[160px]">{{ $r->visitor->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $r->visitor->whatsapp_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 min-w-[140px]">{{ $r->unit->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $checkIn }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $checkOut }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900 text-right tabular-nums whitespace-nowrap">Rp {{ number_format((float) $r->total_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center text-[11px] px-2.5 py-1 rounded-full font-semibold border {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                                        <button type="button" @click='openDetail(@json($detail))'
                                                class="inline-flex items-center justify-center text-xs font-semibold bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 rounded-lg px-3 py-1.5 transition">Detail</button>
                                        @if(in_array($r->status, ['paid', 'confirmed'], true))
                                            <button type="button" onclick="sendBarcode({{ $r->id }}, this)"
                                                    class="inline-flex items-center justify-center text-xs font-bold bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 rounded-lg px-3 py-1.5 transition whitespace-nowrap">Kirim WA</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="9" class="py-12 text-center text-gray-400">Belum ada reservasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div x-show="noResults" x-cloak class="py-10 text-center text-sm text-gray-400 border-t border-gray-100">Tidak ada reservasi yang cocok dengan pencarian.</div>
        </div>

        <div class="block md:hidden">
            <div class="p-3 sm:p-4 space-y-3" id="reservationCards">
                @forelse($reservations as $r)
                    @php
                        $meta = $statusMeta($r->status);
                        $checkIn = trim(($r->check_in_date ?? '-') . ' ' . ($r->check_in_time ? \Illuminate\Support\Str::substr($r->check_in_time, 0, 5) : ''));
                        $checkOut = trim(($r->check_out_date ?? '-') . ' ' . ($r->check_out_time ? \Illuminate\Support\Str::substr($r->check_out_time, 0, 5) : ''));
                        $searchText = strtolower(implode(' ', array_filter([
                            $r->order_code,
                            $r->visitor->name ?? '',
                            $r->visitor->whatsapp_number ?? '',
                            $r->unit->name ?? '',
                            $r->status,
                            $checkIn,
                            $checkOut,
                        ])));
                        $detail = [
                            'id' => $r->id,
                            'order_code' => $r->order_code,
                            'guest_name' => $r->visitor->name ?? '-',
                            'guest_wa' => $r->visitor->whatsapp_number ?? '-',
                            'unit' => $r->unit->name ?? '-',
                            'check_in' => $checkIn,
                            'check_out' => $checkOut,
                            'total' => 'Rp ' . number_format((float) $r->total_price, 0, ',', '.'),
                            'status' => $r->status,
                            'status_label' => $meta['label'],
                            'status_class' => $meta['class'],
                        ];
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-3" data-reservation-item data-search="{{ e($searchText) }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Kode Booking</p>
                                <p class="font-mono font-bold text-sm text-gray-900 break-all">{{ $r->order_code }}</p>
                            </div>
                            <span class="inline-flex items-center justify-center text-[11px] px-2.5 py-1 rounded-full font-semibold border shrink-0 {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Nama Tamu</p>
                                <p class="font-semibold text-gray-800 break-words">{{ $r->visitor->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 break-all">WA: {{ $r->visitor->whatsapp_number ?? '-' }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Paket / Tenda / Unit</p>
                                <p class="font-medium text-gray-800 break-words">{{ $r->unit->name ?? '-' }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Check-in</p>
                                <p class="text-gray-700">{{ $checkIn }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Check-out</p>
                                <p class="text-gray-700">{{ $checkOut }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-gray-100">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Total Harga</p>
                                <p class="text-base font-extrabold text-gray-900 tabular-nums">Rp {{ number_format((float) $r->total_price, 0, ',', '.') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click='openDetail(@json($detail))'
                                        class="inline-flex items-center justify-center min-h-[40px] text-xs font-semibold bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 rounded-lg px-3.5 py-2 transition">Detail</button>
                                @if(in_array($r->status, ['paid', 'confirmed'], true))
                                    <button type="button" onclick="sendBarcode({{ $r->id }}, this)"
                                            class="inline-flex items-center justify-center min-h-[40px] text-xs font-bold bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 rounded-lg px-3.5 py-2 transition">Kirim WA</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400 text-sm">Belum ada reservasi.</div>
                @endforelse
            </div>
            <div x-show="noResults" x-cloak class="px-4 pb-6 text-center text-sm text-gray-400">Tidak ada reservasi yang cocok dengan pencarian.</div>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $reservations->links() }}
        </div>
    </div>

    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeDetail()"></div>
        <div x-show="detailOpen" x-transition class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-100">
                <div class="min-w-0">
                    <p class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Detail Reservasi</p>
                    <p class="font-mono font-bold text-sm text-gray-900 break-all" x-text="selected.order_code || '-'"></p>
                </div>
                <button type="button" @click="closeDetail()" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="inline-flex items-center justify-center text-[11px] px-2.5 py-1 rounded-full font-semibold border" :class="selected.status_class || 'bg-gray-100 text-gray-600 border-gray-200'" x-text="selected.status_label || selected.status || '-'"></span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Nama Tamu</dt>
                        <dd class="font-semibold text-gray-800 break-words" x-text="selected.guest_name || '-'"></dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">WhatsApp</dt>
                        <dd class="text-gray-700 break-all" x-text="selected.guest_wa || '-'"></dd>
                    </div>
                    <div class="sm:col-span-2 min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Unit / Paket</dt>
                        <dd class="text-gray-700 break-words" x-text="selected.unit || '-'"></dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Check-in</dt>
                        <dd class="text-gray-700" x-text="selected.check_in || '-'"></dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Check-out</dt>
                        <dd class="text-gray-700" x-text="selected.check_out || '-'"></dd>
                    </div>
                    <div class="sm:col-span-2 min-w-0">
                        <dt class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Total Harga</dt>
                        <dd class="text-lg font-extrabold text-gray-900 tabular-nums" x-text="selected.total || '-'"></dd>
                    </div>
                </dl>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-end gap-2">
                <button type="button" @click="closeDetail()" class="inline-flex items-center justify-center min-h-[40px] text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-200 rounded-xl px-4 py-2 transition">Tutup</button>
                <template x-if="selected.status === 'paid' || selected.status === 'confirmed'">
                    <button type="button" @click="sendBarcode(selected.id, $event.currentTarget)" class="inline-flex items-center justify-center min-h-[40px] text-sm font-bold bg-teal-600 hover:bg-teal-700 text-white rounded-xl px-4 py-2 transition">Kirim WA</button>
                </template>
            </div>
        </div>
    </div>
</div>

<div id="barcodeToast" class="hidden fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[60] max-w-[calc(100vw-2rem)] text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 bg-green-700 text-white"></div>

<script>
function reservationPage() {
    return {
        query: '',
        noResults: false,
        detailOpen: false,
        selected: {},

        filterReservations() {
            const q = (this.query || '').toLowerCase().trim();
            const items = document.querySelectorAll('[data-reservation-item]');
            let visible = 0;
            items.forEach((el) => {
                const text = (el.getAttribute('data-search') || el.innerText || '').toLowerCase();
                const show = q === '' || text.includes(q);
                el.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const hasAny = document.querySelectorAll('[data-reservation-item]').length > 0;
            this.noResults = hasAny && visible === 0;
        },

        openDetail(data) {
            this.selected = data || {};
            this.detailOpen = true;
        },

        closeDetail() {
            this.detailOpen = false;
        },
    };
}

document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const items = document.querySelectorAll('[data-reservation-item]');
    let visible = 0;
    items.forEach(row => {
        const text = (row.getAttribute('data-search') || row.innerText || '').toLowerCase();
        const show = q === '' || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
});

function showToast(message, isError = false) {
    const toast = document.getElementById('barcodeToast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[60] max-w-[calc(100vw-2rem)] text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 ' +
        (isError ? 'bg-rose-700 text-white' : 'bg-green-700 text-white');
    toast.classList.remove('hidden');
    clearTimeout(window.__barcodeToastTimer);
    window.__barcodeToastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
}

async function sendBarcode(reservationId, button) {
    if (!reservationId) return;
    const btn = button || null;
    const original = btn ? btn.innerHTML : null;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = 'Mengirim...';
    }
    try {
        const res = await fetch('/api/admin/eticket/send/' + reservationId, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        });
        const data = await res.json();
        showToast(data.message ?? (data.success ? 'E-ticket terkirim' : 'Gagal mengirim'), !data.success);
    } catch (e) {
        showToast('Koneksi gagal. Coba lagi.', true);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
}
</script>
@endsection

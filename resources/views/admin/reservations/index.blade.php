@extends('layouts.admin')
@section('page-title', 'Daftar Reservasi')

@section('content')
<div class="bg-white rounded-xl shadow p-5">
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-gray-700">Semua Reservasi</h2>
        <div class="flex gap-2">
            <input type="text" id="searchInput" placeholder="Cari kode / nama..."
                   class="border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-400 outline-none w-52">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="rtable w-full text-sm" id="reservationTable">
            <thead>
                <tr class="text-left text-gray-400 border-b text-xs uppercase tracking-wide">
                    <th class="pb-3 pr-4">Kode</th>
                    <th class="pb-3 pr-4">Tamu</th>
                    <th class="pb-3 pr-4">WhatsApp</th>
                    <th class="pb-3 pr-4">Unit</th>
                    <th class="pb-3 pr-4">Check-in</th>
                    <th class="pb-3 pr-4">Check-out</th>
                    <th class="pb-3 pr-4">Total</th>
                    <th class="pb-3 pr-4">Status</th>
                    <th class="pb-3">Barcode</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($reservations as $r)
                <tr class="hover:bg-gray-50">
                    <td data-label="Kode" class="py-2.5 pr-4 font-mono font-semibold text-xs">{{ $r->order_code }}</td>
                    <td data-label="Tamu" class="py-2.5 pr-4">{{ $r->visitor->name ?? '-' }}</td>
                    <td data-label="WhatsApp" class="py-2.5 pr-4 text-gray-500">{{ $r->visitor->whatsapp_number ?? '-' }}</td>
                    <td data-label="Unit" class="py-2.5 pr-4">{{ $r->unit->name ?? '-' }}</td>
                    <td data-label="Check-in" class="py-2.5 pr-4 text-gray-500">{{ $r->check_in_date }}</td>
                    <td data-label="Check-out" class="py-2.5 pr-4 text-gray-500">{{ $r->check_out_date }}</td>
                    <td data-label="Total" class="py-2.5 pr-4 font-semibold">Rp {{ number_format($r->total_price, 0, ',', '.') }}</td>
                    <td data-label="Status" class="py-2.5 pr-4">
                        @php
                            $colors = [
                                'pending' => 'bg-gray-100 text-gray-500',
                                'waiting_verification' => 'bg-yellow-100 text-yellow-700',
                                'paid' => 'bg-green-100 text-green-800',
                                'confirmed' => 'bg-blue-100 text-blue-700',
                                'checked_in' => 'bg-green-100 text-green-700',
                                'checked_out' => 'bg-indigo-100 text-indigo-500',
                                'cancelled' => 'bg-red-100 text-red-500',
                            ];
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $colors[$r->status] ?? 'bg-gray-100' }}">
                            {{ str_replace('_', ' ', $r->status) }}
                        </span>
                    </td>
                    <td class="py-2.5">
                        @if(in_array($r->status, ['paid', 'confirmed'], true))
                            <button type="button" onclick="sendBarcode({{ $r->id }}, this)"
                                    class="text-xs font-bold bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 rounded-lg px-3 py-1.5 transition whitespace-nowrap">
                                📲 Kirim Barcode
                            </button>
                        @else
                            <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-8 text-center text-gray-400">Belum ada reservasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $reservations->links() }}
    </div>
</div>

<script>
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#reservationTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});

function showToast(message, isError = false) {
    const toast = document.getElementById('barcodeToast');
    toast.textContent = message;
    toast.className = 'fixed bottom-6 right-6 z-50 text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 ' +
        (isError ? 'bg-red-700 text-white' : 'bg-green-700 text-white');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 4000);
}

async function sendBarcode(reservationId, button) {
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳ Mengirim...';
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
        button.disabled = false;
        button.innerHTML = original;
    }
}
</script>

<div id="barcodeToast" class="hidden fixed bottom-6 right-6 z-50 text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 bg-green-700 text-white"></div>
@endsection

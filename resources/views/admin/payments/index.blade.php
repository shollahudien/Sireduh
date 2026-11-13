@extends('layouts.admin')
@section('page-title', 'Verifikasi Pembayaran')

@section('content')
<div class="bg-white rounded-xl shadow p-5">
    <h2 class="font-bold text-gray-700 mb-5">💳 Pembayaran Menunggu Verifikasi</h2>

    <div class="overflow-x-auto">
        <table class="rtable w-full text-sm">
            <thead>
                <tr class="text-left text-gray-400 border-b text-xs uppercase tracking-wide">
                    <th class="pb-3 pr-4">Kode Order</th>
                    <th class="pb-3 pr-4">Tamu</th>
                    <th class="pb-3 pr-4">Total</th>
                    <th class="pb-3 pr-4">Bukti</th>
                    <th class="pb-3 pr-4">Diterima</th>
                    <th class="pb-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td data-label="Kode Order" class="py-3 pr-4 font-mono font-semibold text-xs">
                        {{ $payment->reservation->order_code ?? '-' }}
                    </td>
                    <td data-label="Tamu" class="py-3 pr-4">
                        {{ $payment->reservation->visitor->name ?? '-' }}<br>
                        <span class="text-xs text-gray-400">
                            {{ $payment->reservation->visitor->whatsapp_number ?? '' }}
                        </span>
                    </td>
                    <td data-label="Total" class="py-3 pr-4 font-semibold">
                        Rp {{ number_format($payment->reservation->total_price ?? 0, 0, ',', '.') }}
                    </td>
                    <td data-label="Bukti" class="py-3 pr-4">
                        @if($payment->proof_image)
                            <a href="{{ Storage::url($payment->proof_image) }}" target="_blank"
                               class="text-green-600 hover:underline text-xs">Lihat Bukti ↗</a>
                        @else
                            <span class="text-gray-400 text-xs">Belum ada</span>
                        @endif
                    </td>
                    <td data-label="Diterima" class="py-3 pr-4 text-gray-400 text-xs">
                        {{ $payment->created_at?->format('d M Y H:i') }}
                    </td>
                    <td class="py-3">
                        <form method="POST" action="{{ route('admin.payments.verify', $payment->reservation_id) }}"
                              class="inline"
                              onsubmit="return confirm('Verifikasi pembayaran ini?')">
                            @csrf
                            @method('PUT')
                            <button type="submit"
                                    class="bg-green-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-green-700 transition font-semibold">
                                ✅ Verifikasi
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400">
                        Tidak ada pembayaran yang menunggu verifikasi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</div>
@endsection

@extends('layouts.admin')
@section('page-title', 'Verifikasi Pembayaran')

@section('content')
<div class="bg-white rounded-xl shadow p-5">
    <h2 class="font-bold text-gray-700 mb-5">💳 Pembayaran Menunggu Verifikasi</h2>

    <div class="overflow-x-auto">
        <table class="rtable w-full text-sm min-w-[760px]">
            <thead>
                <tr class="text-left text-gray-400 border-b text-xs uppercase tracking-wide">
                    <th class="pb-3 pr-4">Kode Order</th>
                    <th class="pb-3 pr-4">Tamu</th>
                    <th class="pb-3 pr-4">Metode / Ref</th>
                    <th class="pb-3 pr-4">Total</th>
                    <th class="pb-3 pr-4">Bukti</th>
                    <th class="pb-3 pr-4">Status</th>
                    <th class="pb-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($payments as $payment)
                @php
                    $pStatus = $payment->status;
                    $pBadge = match($pStatus){ 'verified' => 'bg-green-100 text-green-800', 'pending','waiting_verification' => 'bg-yellow-100 text-yellow-800', 'expired' => 'bg-gray-200 text-gray-700', 'failed','rejected' => 'bg-red-100 text-red-700', default => 'bg-gray-100 text-gray-700' };
                    $methodLabel = ($payment->method ?? 'manual_transfer') === 'gateway' ? 'Instan ('.($payment->provider ?? 'gateway').')' : 'Transfer Manual';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td data-label="Kode Order" class="py-3 pr-4 font-mono font-semibold text-xs">
                        {{ $payment->reservation->order_code ?? '-' }}
                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $payment->created_at?->format('d M Y H:i') }}</div>
                    </td>
                    <td data-label="Tamu" class="py-3 pr-4">
                        {{ $payment->reservation->visitor->name ?? '-' }}<br>
                        <span class="text-xs text-gray-400">
                            {{ $payment->reservation->visitor->whatsapp_number ?? '' }}
                        </span>
                    </td>
                    <td data-label="Metode / Ref" class="py-3 pr-4 text-xs text-gray-700">
                        <div class="font-semibold">{{ $methodLabel }}</div>
                        @if($payment->payment_type)<div class="text-[11px] text-gray-500">Tipe: {{ $payment->payment_type }}</div>@endif
                        @if($payment->provider_order_id)<div class="text-[10px] font-mono text-gray-400 break-all">Ref: {{ $payment->provider_order_id }}</div>@endif
                    </td>
                    <td data-label="Total" class="py-3 pr-4 font-semibold">
                        Rp {{ number_format($payment->reservation->total_price ?? 0, 0, ',', '.') }}
                    </td>
                    <td data-label="Bukti" class="py-3 pr-4">
                        @if($payment->proof_image)
                            <a href="{{ route('admin.proofs.show', $payment->reservation) }}" target="_blank"
                               class="text-green-600 hover:underline text-xs">Lihat Bukti ↗</a>
                        @elseif(($payment->method ?? '') === 'gateway')
                            <span class="text-gray-400 text-xs">Gateway (otomatis)</span>
                        @else
                            <span class="text-gray-400 text-xs">Belum ada</span>
                        @endif
                    </td>
                    <td data-label="Status" class="py-3 pr-4"><span class="text-[10px] font-bold uppercase rounded-full px-2.5 py-1 {{ $pBadge }}">{{ $pStatus }}</span></td>
                    <td class="py-3">
                        <div class="flex items-center gap-2">
                            @if(in_array($pStatus, ['pending','waiting_verification']))
                            <form method="POST" action="{{ route('admin.payments.verify', $payment->reservation_id) }}" class="inline" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                @csrf @method('PUT')
                                <button type="submit" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-green-700 transition font-semibold">✅ Verifikasi</button>
                            </form>
                            <form method="POST" action="{{ route('admin.payments.reject', $payment->reservation_id) }}" class="inline" onsubmit="return confirm('Tolak pembayaran ini? Reservasi akan dibatalkan.')">
                                @csrf @method('PUT')
                                <button type="submit" class="bg-red-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-red-700 transition font-semibold">❌ Tolak</button>
                            </form>
                            @elseif(in_array($pStatus, ['expired','failed']))
                                <span class="text-xs text-gray-500">Gagal/kedaluwarsa — sarankan bayar ulang/transfer manual</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-gray-400">
                        Tidak ada pembayaran yang perlu tindak lanjut (pending/expired/failed).
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</div>
@endsection

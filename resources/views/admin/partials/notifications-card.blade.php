<!-- Notification Card for Dashboard -->
@if($newPayments->count() > 0)
<div class="fixed top-4 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-in fade-in-0">
    <div class="flex items-center gap-3">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 9l-3 3m-3-3l3 3m6-3l-3-3m-6 3l3-3m0 9l3 3m-3-3l-3 3"/>
        </svg>
        <div>
            <p class="font-medium">{{ $newPayments->count() }} Pembayaran Baru</p>
            <p class="text-sm opacity-90">Menunggu verifikasi admin</p>
        </div>
    </div>
</div>
@endif
{{-- Bottom navigation bar — gaya aplikasi mobile pada umumnya (area check-in) --}}
<nav class="fixed inset-x-0 bottom-0 z-40 bg-teal-900/95 backdrop-blur border-t border-teal-800" style="padding-bottom: env(safe-area-inset-bottom)">
    <div class="grid grid-cols-3 h-16 max-w-5xl mx-auto">
        @php($is = fn (string $route) => request()->routeIs($route))
        <a href="{{ route('checkin.officer.index') }}"
           class="flex flex-col items-center justify-center gap-0.5 {{ $is('checkin.officer.index') ? 'text-teal-300' : 'text-teal-500/70 hover:text-teal-300' }} transition">
            <span class="text-2xl leading-none">📷</span>
            <span class="text-[10px] font-semibold uppercase tracking-wide {{ $is('checkin.officer.index') ? 'border-t-2 border-teal-400 pt-0.5' : '' }}">Scanner</span>
        </a>
        <a href="{{ route('checkin.officer.tiket') }}"
           class="flex flex-col items-center justify-center gap-0.5 {{ $is('checkin.officer.tiket') ? 'text-teal-300' : 'text-teal-500/70 hover:text-teal-300' }} transition">
            <span class="text-2xl leading-none">🎟️</span>
            <span class="text-[10px] font-semibold uppercase tracking-wide {{ $is('checkin.officer.tiket') ? 'border-t-2 border-teal-400 pt-0.5' : '' }}">Tiket</span>
        </a>
        <a href="{{ route('checkin.officer.riwayat') }}"
           class="flex flex-col items-center justify-center gap-0.5 {{ $is('checkin.officer.riwayat') ? 'text-teal-300' : 'text-teal-500/70 hover:text-teal-300' }} transition">
            <span class="text-2xl leading-none">📋</span>
            <span class="text-[10px] font-semibold uppercase tracking-wide {{ $is('checkin.officer.riwayat') ? 'border-t-2 border-teal-400 pt-0.5' : '' }}">Riwayat</span>
        </a>
    </div>
</nav>

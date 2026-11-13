{{-- Maskot robot Teduh Bot — melayang, antena bergoyang, mata berkedip.
     Pakai: <x-bot-mascot size="w-10 h-10" bubble="Halo Admin!" tone="lime" /> --}}
@props([
    'size' => 'w-10 h-10',
    'bubble' => null,
    'tone' => 'lime',
])
@php
    $tones = [
        'lime' => ['body' => 'url(#botmBodyLime)', 'glow' => '#a3e635'],
        'amber' => ['body' => 'url(#botmBodyAmber)', 'glow' => '#fbbf24'],
    ];
    $t = $tones[$tone] ?? $tones['lime'];
@endphp
<div class="bot-mascot relative shrink-0 {{ $size }}" aria-hidden="true">
    <svg class="w-full h-full drop-shadow-lg" viewBox="0 0 100 104" fill="none">
        <defs>
            <linearGradient id="botmBodyLime" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#22c55e"/>
                <stop offset="100%" stop-color="#0f766e"/>
            </linearGradient>
            <linearGradient id="botmBodyAmber" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f59e0b"/>
                <stop offset="100%" stop-color="#b45309"/>
            </linearGradient>
        </defs>

        {{-- Antena bergoyang --}}
        <g class="botm-antenna">
            <line x1="50" y1="30" x2="50" y2="16" stroke="{{ $t['glow'] }}" stroke-width="3.4" stroke-linecap="round"/>
            <circle cx="50" cy="11" r="5" fill="{{ $t['glow'] }}" stroke="#14532d" stroke-width="2"/>
        </g>

        {{-- Kepala --}}
        <rect x="20" y="30" width="60" height="58" rx="16" fill="{{ $t['body'] }}" stroke="#14532d" stroke-width="2"/>
        {{-- Telinga antena samping --}}
        <rect x="12" y="50" width="8" height="14" rx="4" fill="{{ $t['glow'] }}" opacity="0.85"/>
        <rect x="80" y="50" width="8" height="14" rx="4" fill="{{ $t['glow'] }}" opacity="0.85"/>

        {{-- Layar wajah --}}
        <rect x="30" y="40" width="40" height="36" rx="11" fill="#052e23"/>

        {{-- Mata berkedip --}}
        <g class="botm-eye">
            <circle cx="41" cy="55" r="5.4" fill="#f0fdf4"/>
            <circle cx="59" cy="55" r="5.4" fill="#f0fdf4"/>
            <circle cx="41.6" cy="55.6" r="2.5" fill="#052e23"/>
            <circle cx="59.6" cy="55.6" r="2.5" fill="#052e23"/>
            <circle cx="42.6" cy="54.2" r="0.9" fill="#d9f99d"/>
            <circle cx="60.6" cy="54.2" r="0.9" fill="#d9f99d"/>
        </g>

        {{-- Mulut senyum --}}
        <path d="M44 66 Q50 70 56 66" stroke="{{ $t['glow'] }}" stroke-width="2.4" stroke-linecap="round" fill="none"/>
    </svg>

    {{-- Gelembung bicara kecil --}}
    @if($bubble)
        <span class="botm-bubble absolute -top-1.5 left-full ml-1 whitespace-nowrap rounded-full bg-white px-2 py-0.5 text-[9px] sm:text-[10px] font-bold text-green-900 shadow-md ring-1 ring-green-200 z-10">
            {{ $bubble }}
        </span>
    @endif
</div>

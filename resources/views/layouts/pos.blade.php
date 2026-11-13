<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS Kasir') &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="sticky top-0 z-30">
        <div class="navbar-shimmer h-[3px] bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400"></div>
        <div class="bg-emerald-700 text-white shadow-lg">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-3 px-3 sm:px-6 py-2.5 sm:py-3">
                <a href="{{ route('pos.officer.index') }}"
                   class="group flex items-center gap-2.5 min-w-0 focus-visible:ring-2 focus-visible:ring-lime-300 rounded-xl">
                    <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-lime-400/20 ring-1 ring-lime-300/40 flex items-center justify-center text-lg sm:text-xl transition-transform duration-300 group-hover:scale-110 group-active:scale-95">🏕️</span>
                    <span class="leading-tight min-w-0">
                        <span class="block font-extrabold tracking-wide text-sm sm:text-base">SIRESTEDUH</span>
                        <span class="block text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.2em] text-lime-300/90">POS Kasir</span>
                    </span>
                </a>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <div class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-white/20">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-lime-400 text-emerald-950 flex items-center justify-center text-xs sm:text-sm font-extrabold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden sm:block text-sm font-semibold">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 text-xs sm:text-sm font-semibold px-3 sm:px-4 py-2 rounded-xl text-red-200 hover:text-white hover:bg-red-500/20 hover:-translate-y-0.5 ring-1 ring-red-400/30 hover:ring-red-400/60 active:scale-95 transition-all duration-200">
                            🚪 <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-[1600px] px-4 py-4 sm:px-6">@yield('content')</main>
    @stack('scripts')
</body>
</html>

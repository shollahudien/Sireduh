<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIRESTEDUH') &mdash; BUMDes Wisata Teduh</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased" data-layout="public">
    <nav class="border-b border-stone-200 bg-white/95 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="font-semibold tracking-wide text-emerald-900">SIRESTEDUH</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ url('/checkout') }}" class="text-stone-600 transition hover:text-emerald-700">Reservasi</a>
                <a href="{{ route('login') }}" class="rounded-lg bg-emerald-800 px-4 py-2 font-medium text-white transition hover:bg-emerald-700">Masuk</a>
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif

    <main>@yield('content')</main>

    <footer class="mt-12 border-t border-stone-200 bg-white py-6 text-center text-sm text-stone-500">
        &copy; {{ date('Y') }} BUMDes Wisata Teduh Glamping
    </footer>
    @stack('scripts')
</body>
</html>

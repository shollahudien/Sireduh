<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Check-in') &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @stack('head')
</head>
<body class="min-h-screen bg-teal-950 text-slate-900 antialiased">
    <header class="border-b border-teal-800 bg-teal-900 text-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('checkin.officer.index') }}" class="font-bold tracking-wide">SIRESTEDUH CHECK-IN</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-teal-700 px-3 py-2 text-sm hover:bg-teal-800">Keluar</button>
            </form>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">@yield('content')</main>
    @stack('scripts')
</body>
</html>

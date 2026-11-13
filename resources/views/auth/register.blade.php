<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-green-50">
    <div class="w-full max-w-md mx-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <div class="text-4xl mb-2">🏕️</div>
                <h1 class="text-2xl font-bold text-green-800">SIRESTEDUH</h1>
                <p class="text-sm text-gray-400 mt-1">Buat akun pengunjung baru</p>
            </div>

            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                </div>
                <button type="submit"
                        class="w-full bg-green-700 text-white py-2.5 rounded-lg font-semibold hover:bg-green-800 transition">
                    Daftar Sekarang
                </button>
            </form>

            <p class="text-center text-sm text-gray-400 mt-4">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-green-600 hover:underline font-semibold">Masuk</a>
            </p>
        </div>
    </div>
</body>
</html>

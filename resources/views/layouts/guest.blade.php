<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRESTEDUH - Reservasi Terpadu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <script src="https://unpkg@tailwindcss@latest/dist@tailwind.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    
    <!-- Navigation Mobile -->
    <nav class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6-6h12m-6-6h6"/>
                </svg>
                SIRESTEDUH
            </a>
            <button id="menu-mobile" class="md:hidden p-2 rounded-lg hover:bg-gray-100" aria-label="Buka menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </nav>

    <main class="pt-20 pb-12">
        <div class="max-w-4xl mx-auto px-4 py-12 text-center">
            <div class="animate-fade-in">
                <h1 class="text-5xl font-bold text-primary mb-4">Selamat Datang di SIRESTEDUH</h1>
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                    Sistem Informasi Reservasi Terpadu untuk unit glamping dan aula. 
                    Booking unit dan lakukan pembayaran dengan mudah.
                </p>
            </div>

            <!-- Search & Select Form -->
            <div class="bg-white rounded-2xl p-8 md:p-10 shadow-xl max-w-2xl mx-auto animate-fade-in-delay">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Pilih Unit & Tanggal</h2>
                
                <form action="{{ route('guest.checkout') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Check-in</label>
                            <input type="date" name="check_in_date" 
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Check-out</label>
                            <input type="date" name="check_out_date"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                   required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Tamu</label>
                            <input type="number" name="guest_count" min="1" value="1"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kategori Unit</label>
                            <select name="unit_type" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                <option value="glamping">Glamping</option>
                                <option value="aula">Aula</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produk Tambahan (Add-ons)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer">
                                <input type="checkbox" name="addons[]" value="coffee" class="rounded border-gray-400">
                                <span>Kopi Local</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer">
                                <input type="checkbox" name="addons[]" value="souvenir" class="rounded border-gray-400">
                                <span>Souvenir</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer">
                                <input type="checkbox" name="addons[]" value="food" class="rounded border-gray-400">
                                <span>Makanan</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <h3 class="font-medium text-gray-700 mb-2">Harga Estimated</h3>
                        <p class="text-3xl font-bold text-primary">Rp 0</p>
                    </div>
                    
                    <button type="submit"
                            class="w-full py-4 px-6 rounded-xl bg-primary text-white font-medium hover:bg-primary/90 transition-colors">
                        Lanjut ke Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400 text-sm">SIRESTEDUH &copy; 2026</p>
        </div>
    </footer>

</body>
</html>
<style>
    :root {
        --primary: #18a0bc;
        --primary-dark: #1580a0;
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    .animate-fade-in-delay {
        animation: fadeIn 0.6s ease-out 0.3s forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in {
            animation: none;
            opacity: 1;
        }
    }
</style>
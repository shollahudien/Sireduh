<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRESTEDUH - Pemesanan Unit</title>
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
                <h1 class="text-5xl font-bold text-primary mb-4">Pemesanan Unit</h1>
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                    Pilih unit, tanggal, dan produk tambahan untuk membuat reservasi.
                </p>
            </div>

            <!-- Search & Select Form with Alpine.js -->
            <div class="bg-white rounded-2xl p-8 md:p-10 shadow-xl max-w-2xl mx-auto animate-fade-in-delay">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Pilih Unit & Tanggal</h2>
                
                <form id="reservationForm" 
                      x-data="{ 
                          selectedUnit: null, 
                          selectedAddons: [],
                          checkIn: '',
                          checkOut: '',
                          guestCount: 1,
                          totalPrice: 0,
                          loading: false
                      }"
                      x-init="fetchAvailableUnits"
                      @submit.prevent="submitReservation"
                      class="space-y-4">
                    
                    <!-- Date Selection -->
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Check-in</label>
                            <input type="date" name="check_in_date" 
                                   x-model="checkIn"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Check-out</label>
                            <input type="date" name="check_out_date" 
                                   x-model="checkOut"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                   required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Tamu</label>
                            <input type="number" name="guest_count" min="1" value="1"
                                   x-model.number="guestCount"
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
                    
                    <!-- Available Units will appear here via Alpine.js -->
                    <div id="units-list" x-show="loading" class="pt-4 text-center text-gray-500">
                        <p class="text-sm">Sedang memuat unit...</p>
                    </div>
                    
                    <div x-show="!loading" x-transition class="space-y-4">
                        <!-- Units will be dynamically listed here -->
                    </div>
                    
                    <!-- Add-ons Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Produk Tambahan (Add-ons)</label>
                        <div class="grid grid-cols-2 gap-3" x-show="selectedUnit">
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer group">
                                <input type="checkbox" name="addons[]" value="coffee" 
                                       @checked="selectedAddons.includes('coffee')" 
                                       class="rounded border-gray-400 cursor-pointer group-hover:border-primary">
                                <span>Kopi Local - Rp 25.000</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer group">
                                <input type="checkbox" name="addons[]" value="souvenir" 
                                       @checked="selectedAddons.includes('souvenir')" 
                                       class="rounded border-gray-400 cursor-pointer group-hover:border-primary">
                                <span>Souvenir - Rp 15.000</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-gray-300 hover:border-primary cursor-pointer group">
                                <input type="checkbox" name="addons[]" value="food" 
                                       @checked="selectedAddons.includes('food')" 
                                       class="rounded border-gray-400 cursor-pointer group-hover:border-primary">
                                <span>Makanan - Rp 30.000</span>
                            </label>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <h3 class="font-medium text-gray-700 mb-2">Harga Estimated</h3>
                            <p class="text-3xl font-bold text-primary" x-text="totalPrice">Rp 0</p>
                        </div>
                    </div>
                    
                    <button type="submit"
                            class="w-full py-4 px-6 rounded-xl bg-primary text-white font-medium hover:bg-primary/90 transition-colors"
                            :disabled="loading">
                        @if(loading)
                            <span class="inline-block animate-spin h-4 w-4 mr-2"></span>
                            Sedang memproses...
                        @else
                            Lanjut ke Pembayaran
                        @endif
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

    <script>
        // Alpine.js initialization for availability fetching
        document.addEventListener('alpine:init', () => {
            Alpine.data('reservationApp', () => ({
                selectedUnit: null,
                selectedAddons: [],
                checkIn: '',
                checkOut: '',
                guestCount: 1,
                totalPrice: 0,
                loading: false,
                
                fetchAvailableUnits() {
                    // Check if we have valid dates
                    if (!this.checkIn || !this.checkOut) return;
                    
                    this.loading = true;
                    fetch('/api/availability', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            check_in_date: this.checkIn,
                            check_out_date: this.checkOut,
                            unit_type: document.querySelector('select[name="unit_type"]').value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.loading = false;
                        // Render available units
                        const unitsContainer = document.getElementById('units-list');
                        const unitType = document.querySelector('select[name="unit_type"]').value;
                        
                        if (data.available_units && data.available_units.length > 0) {
                            let unitsHTML = '';
                            data.available_units.forEach(unit => {
                                unitsHTML += `
                                    <div class="bg-white rounded-2xl p-6 shadow-sm hover:border-primary transition-colors cursor-pointer" 
                                         @click="selectUnit(unit)"
                                         style="border: 2px solid #18a0bc;">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-medium text-gray-900">${unit.name}</h3>
                                        <span class="text-sm text-primary">Rp ${unit.price}/malam</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Kapasitas: ${unit.capacity} tamu</p>
                                    <p class="text-xs text-gray-500">Jenis: ${unit.type}</p>
                                `;
                                if (unit.capacity >= this.guestCount) {
                                    unitsHTML += `<p class="text-green-600 font-medium">Tersedia untuk ${this.guestCount} tamu</p>`;
                                } else {
                                    unitsHTML += `<p class="text-red-600 font-medium">Terlalu sedikit kapasitas</p>`;
                                }
                                unitsHTML += `</div>`;
                            });
                            unitsContainer.innerHTML = unitsHTML;
                        } else {
                            unitsContainer.innerHTML = `
                                <div class="bg-red-100 border-red-400 border-4 rounded-4 p-8 text-center">
                                    <p class="text-red-600">Maaf, unit tidak tersedia pada tanggal tersebut.</p>
                                    <p class="text-sm mt-2">Silakan pilih tanggal lain.</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        this.loading = false;
                        console.error('Error fetching availability:', error);
                        alert('Gagal memuat data ketersediaan. Silakan coba lagi.');
                    });
                },
                
                selectUnit(unit) {
                    this.selectedUnit = unit;
                    // Recalculate total price
                    this.calculateTotal();
                },
                
                calculateTotal() {
                    if (!this.selectedUnit) {
                        this.totalPrice = 0;
                        return;
                    }
                    
                    // Calculate nights
                    const checkInDate = new Date(this.checkIn);
                    const checkOutDate = new Date(this.checkOut);
                    const nights = Math.max(1, Math.ceil((checkOutDate - checkInDate) / 86400000));
                    
                    // Base price: price * nights
                    const basePrice = this.selectedUnit.price * nights;
                    
                    // Add-ons price
                    let addonsPrice = 0;
                    const addonPrices = {
                        'coffee': 25000,
                        'souvenir': 15000,
                        'food': 30000
                    };
                    
                    this.selectedAddons.forEach(addon => {
                        if (addonPrices[addon]) {
                            addonsPrice += addonPrices[addon];
                        }
                    });
                    
                    // Total price
                    this.totalPrice = basePrice + addonsPrice;
                },
                
                submitReservation() {
                    if (!this.selectedUnit || !this.checkIn || !this.checkOut) {
                        alert('Silakan pilih unit terlebih dahulu.');
                        return;
                    }
                    
                    // Calculate total price one more time
                    this.calculateTotal();
                    
                    // Gather addons from checkboxes
                    const addons = [];
                    document.querySelectorAll('input[name="addons[]"]:checked').forEach(cb => {
                        addons.push(cb.value);
                    });
                    
                    fetch('/api/reservations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            visitor_name: '${auth.name || "Nama Tamu"}',
                            visitor_whatsapp: '${auth.whatsapp || ""}',
                            visitor_email: '${auth.email || ""}',
                            unit_id: this.selectedUnit.id,
                            check_in_date: this.checkIn,
                            check_out_date: this.checkOut,
                            guest_count: this.guestCount,
                            addons: addons,
                            total_price: this.totalPrice
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message and redirect or show confirmation
                            alert('Reservasi berhasil dibuat!\n\nOrder Code: ' + data.reservation.order_code + '\nStatus: ' + data.reservation.status + '\nTotal: Rp ' + data.reservation.total_price);
                            
                            // Reset form
                            document.getElementById('reservationForm').reset();
                            this.selectedUnit = null;
                            this.selectedAddons = [];
                            this.totalPrice = 0;
                            this.loading = false;
                            
                            // Redirect to payment page or show success
                            window.location.href = '/checkout';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting reservation:', error);
                        alert('Gagal membuat reservasi. Silakan coba lagi.');
                    });
                }
            }));
        });
    </script>

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
@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
{{-- Hero --}}
<div class="relative rounded-2xl overflow-hidden bg-green-800 text-white mb-8 sm:mb-12 min-h-[300px] sm:min-h-[360px]"
     style="background-image:linear-gradient(135deg,#14532d 0%,#166534 50%,#15803d 100%);">
    <div class="absolute inset-0 opacity-10"
         style="background-image:url('https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=1200');
                background-size:cover;background-position:center;"></div>
    {{-- Nuansa teknologi: grid bergerak + sapuan scan --}}
    <div class="absolute inset-0 tech-grid opacity-70 pointer-events-none"></div>
    <div class="absolute inset-x-0 top-0 h-20 sm:h-28 bg-gradient-to-b from-lime-300/30 to-transparent tech-scan pointer-events-none"></div>
    <div class="relative z-10 flex flex-col items-center justify-center text-center px-4 sm:px-6 py-14 sm:py-24">
        <span class="inline-flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-[0.18em] text-lime-300 bg-black/30 ring-1 ring-lime-300/30 rounded-full px-3 py-1.5 mb-5">
            <span class="tech-dot w-2 h-2 rounded-full bg-lime-400"></span>
            Sistem Reservasi Online
        </span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4 drop-shadow">🏕️ BUMDes Wisata Teduh</h1>
        <p class="text-base sm:text-lg md:text-xl text-green-100 mb-8 max-w-xl">
            Pengalaman glamping & wisata alam terbaik di pedesaan. Reservasi mudah, check-in cepat.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto max-w-xs sm:max-w-none mx-auto">
            <a href="{{ route('ticket.index') }}"
               class="bg-yellow-400 text-green-950 font-bold px-6 sm:px-8 py-3 rounded-full shadow-lg hover:bg-yellow-300 active:scale-95 transition text-base sm:text-lg text-center">
                🎫 Beli Tiket Masuk
            </a>
            <a href="{{ route('checkout') }}"
               class="bg-white text-green-800 font-bold px-6 sm:px-8 py-3 rounded-full shadow-lg hover:bg-green-50 active:scale-95 transition text-base sm:text-lg text-center">
                🏕️ Reservasi Menginap
            </a>
        </div>
    </div>
</div>

{{-- Unit List --}}
<section>
    <h2 class="text-2xl font-bold text-green-800 mb-6">Unit Tersedia</h2>
    <div x-data="unitList()" x-init="load()" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-if="loading">
            <div class="col-span-full text-center text-gray-400 py-12">Memuat unit...</div>
        </template>
        <template x-for="unit in units" :key="unit.id">
            <div class="bg-white rounded-xl shadow hover:shadow-md transition border border-gray-100 overflow-hidden">
                <template x-if="unit.images?.length">
                    <img loading="lazy" :src="unit.images?.[0]?.url" :alt="unit.name" class="w-full h-40 object-cover">
                </template>
                <div x-show="!unit.images?.length" x-cloak class="bg-green-100 h-40 flex items-center justify-center text-5xl">🏕️</div>
                <div class="p-5">
                    <h3 class="font-bold text-lg text-gray-800" x-text="unit.name"></h3>
                    <p class="text-sm text-gray-500 capitalize mb-2" x-text="unit.type"></p>
                    <p class="text-green-700 font-semibold text-lg"
                       x-text="'Rp ' + parseInt(unit.price).toLocaleString('id-ID') + ' / malam'"></p>
                    <p class="text-xs text-gray-400 mb-4" x-text="'Kapasitas: ' + unit.capacity + ' orang'"></p>
                    <a href="{{ url('/checkout') }}"
                       class="block w-full text-center bg-green-700 text-white py-2 rounded-lg hover:bg-green-800 transition font-semibold">
                        Reservasi
                    </a>
                </div>
            </div>
        </template>
        <template x-if="!loading && units.length === 0">
            <div class="col-span-full text-center text-gray-400 py-12">Belum ada unit tersedia.</div>
        </template>
    </div>
</section>

<script>
function unitList() {
    return {
        units: [],
        loading: true,
        async load() {
            try {
                const r = await fetch('/api/units');
                const json = await r.json();
                this.units = json.data ?? [];
            } catch(e) {
                this.units = [];
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection

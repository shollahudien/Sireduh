@extends('layouts.admin')
@section('page-title', 'Produk')

@section('content')
<div x-data="productsPage()" x-init="load()">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-[var(--color-error)]' : 'bg-[var(--color-brand-primary)]'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 flex items-center gap-3">
        <span x-text="toast.isError ? '⚠️' : '✅'"></span><span x-text="toast.message"></span>
    </div>

    <div class="bg-white dark:bg-gray-900 dark:text-gray-100 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">🛍️ Produk</h2>
                <p class="text-xs text-gray-400">F&amp;B, Souvenir, Merchandise, UMKM &amp; Tiket Masuk.</p>
            </div>
            <button @click="openModal()" class="bg-[var(--color-brand-primary)] text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-[var(--color-brand-secondary)] transition shadow">
                + Tambah Produk
            </button>
        </div>

        {{-- Filter tabs --}}
        <div class="px-4 sm:px-6 pt-4 flex gap-1.5 overflow-x-auto text-xs sm:text-sm border-b border-gray-100 pb-4">
            <template x-for="tab in tabs" :key="tab.value">
                <button type="button" @click="activeTab = tab.value"
                        :class="activeTab === tab.value ? 'bg-[var(--color-brand-primary)] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="shrink-0 px-3.5 py-2 rounded-full font-semibold transition">
                    <span x-text="tab.icon + ' ' + tab.label"></span>
                    <span class="text-[10px] opacity-80" x-text="' (' + countByTab(tab.value) + ')'"></span>
                </button>
            </template>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="rtable w-full text-sm">
                <thead>
<tr class="text-left text-gray-400 dark:text-gray-300 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-4 sm:pl-6">Nama</th>
                        <th class="pb-3 pr-4">Kategori</th>
                        <th class="pb-3 pr-4 text-right">Harga</th>
                        <th class="pb-3 pr-4 text-center">Tipe</th>
                        <th class="pb-3 pr-4 text-center">F&B</th>
                        <th class="pb-3 pr-4">Stok</th>
                        <th class="pb-3 pr-4 sm:pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-16 text-center text-gray-400">Memuat produk...</td></tr>
                    </template>
                    <template x-if="!loading && filteredProducts.length === 0">
                        <tr><td colspan="7" class="py-16 text-center text-gray-400">Tidak ada produk pada kategori ini.</td></tr>
                    </template>
                    <template x-for="product in filteredProducts" :key="product.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 align-middle">
                            <td data-label="Nama" class="py-3 pr-4 pl-4 sm:pl-6 dark:text-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img x-show="product.images?.length" :src="product.images?.[0]?.url" :alt="product.name" class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg object-cover border border-gray-100 dark:border-gray-200 shrink-0" x-cloak>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200 leading-snug break-words" x-text="product.name"></span>
                                </div>
                            </td>
                            <td data-label="Kategori" class="py-3 pr-4">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5 bg-gray-100/50 text-gray-600 dark:bg-gray-800/30 dark:text-gray-300" x-text="categoryLabel(product.category)"></span>
                            </td>
                            <td data-label="Harga" class="py-3 pr-4 text-right font-semibold text-green-800 dark:text-green-300" x-text="formatIDR(product.price)"></td>
                            <td data-label="Tipe" class="py-3 pr-4 text-center">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5"
                                      :class="product.type === 'addon' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30'"
                                      x-text="product.type?.toUpperCase()"></span>
                            </td>
                            <td data-label="F&B" class="py-3 pr-4 text-center">
                                <button type="button" @click="toggleForKasirMakanan(product)"
                                        :title="product.for_kasir_makanan ? 'Tampil di kasir makanan — klik untuk sembunyikan' : 'Klik untuk tampilkan di kasir makanan'"
                                        :class="product.for_kasir_makanan ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-gray-50 text-gray-400 border-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="text-[10px] font-extrabold rounded-full px-2.5 py-1 border transition">
                                    <span x-text="product.for_kasir_makanan ? 'YA' : '—'"></span>
                                </button>
                            </td>
                            <td data-label="Stok" class="py-3 pr-4">
                                <div class="flex items-center gap-1.5">
                                    <input type="number" min="0" :value="product.stock"
                                           @keydown.enter="$event.target.blur()"
                                           @change="updateStock(product, $event.target.value)"
                                           class="w-16 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500">
                                    <span class="text-xs text-green-600 font-bold" x-show="stockSavedId === product.id" x-cloak>✓</span>
                                </div>
                            </td>
                            <td class="py-3 pr-4 sm:pr-6">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <button @click="openModal(product)" class="focus-visible:ring-2 focus-visible:ring-lime-300 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition">✏️ Edit</button>
                                    <button @click="destroy(product)"
                                            :disabled="productHasHistory(product)"
                                            :title="productHasHistory(product) ? 'Punya riwayat transaksi — tidak bisa dihapus' : 'Hapus produk'"
                                            class="focus-visible:ring-2 focus-visible:ring-lime-300 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add/Edit modal --}}
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <form @submit.prevent="save()" class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800" x-text="form.id ? '✏️ Edit Produk' : '🛍️ Tambah Produk'"></h3>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none" aria-label="Tutup modal">×</button>
            </div>
            <div class="p-6 space-y-3 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="text-xs font-semibold text-gray-500">Nama Produk</label>
                    <input type="text" x-model="form.name" required maxlength="255" placeholder="Kopi Ijen Gayo"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Kategori</label>
                        <select x-model="form.category" required @change="onCategoryChange()"
                                class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="tiket_masuk">Tiket Masuk</option>
                            <option value="makanan">Makanan</option>
                            <option value="minuman">Minuman</option>
                            <option value="souvenir">Souvenir</option>
                            <option value="baju_merchandise">Baju &amp; Merchandise</option>
                            <option value="produk_umkm">Produk UMKM</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Tipe</label>
                        <select x-model="form.type" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="addon">Addon (Online)</option>
                            <option value="pos">POS (Kasir)</option>
                        </select>
                    </div>
                </div>
                <p x-show="form.category === 'tiket_masuk'" x-cloak class="text-[11px] text-gray-500 bg-gray-50 rounded-xl px-3 py-2">
                    🎫 <span class="font-semibold">Addon (Online)</span> = dijual di checkout web &middot; <span class="font-semibold">POS (Kasir)</span> = dijual petugas loket/check-in.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Harga (Rp)</label>
                        <input type="number" min="0" x-model.number="form.price" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Stok</label>
                        <input type="number" min="0" x-model.number="form.stock" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>

                <label class="flex items-start gap-2 border border-amber-200 bg-amber-50 rounded-xl px-3 py-2 cursor-pointer">
                    <input type="checkbox" x-model="form.for_kasir_makanan" class="mt-0.5 accent-amber-600">
                    <span class="text-xs text-amber-900">Tampilkan ke <span class="font-bold">Kasir Makanan (F&amp;B)</span> — kasir makanan hanya menjual produk yang dicentang.</span>
                </label>

                {{-- Image gallery --}}
                <div x-show="form.id">
                    <label class="text-xs font-semibold text-gray-500">Foto Produk <span class="text-gray-400">(klien bisa lihat galeri ini)</span></label>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        <template x-for="(img, i) in form.images" :key="img.id">
                            <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 group">
                                <img :src="img.url" loading="lazy" :alt="img.alt || 'foto produk'" class="w-full h-full object-cover">
                                <button type="button" @click="removeImage(img)"
                                        class="absolute top-1 right-1 bg-black/60 hover:bg-red-600 text-white rounded-full w-6 h-6 text-[11px] leading-none transition">✕</button>
                                <span class="absolute bottom-1 left-1 bg-black/55 text-white text-[10px] rounded px-1.5 py-0.5" x-text="'#' + (i + 1)"></span>
                            </div>
                        </template>
                        <label class="aspect-square rounded-xl border-2 border-dashed border-gray-200 flex flex-col items-center justify-center text-gray-400 hover:border-green-400 hover:text-green-700 cursor-pointer transition text-[11px] font-semibold gap-1">
                            <span class="text-2xl">📷</span>
                            <span>Tambah Foto</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="uploadImages($event)">
                        </label>
                    </div>
                    <p x-show="uploading" x-cloak class="text-xs text-blue-600 mt-2">Mengunggah...</p>
                    <p x-show="uploadError" x-cloak class="text-xs text-red-600 mt-2" x-text="uploadError"></p>
                </div>
                <div x-show="!form.id" x-cloak class="text-[11px] text-gray-400 bg-gray-50 rounded-xl px-3 py-2">
                    💾 Simpan produk dulu untuk menambahkan foto.
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center gap-2 justify-end border-t border-gray-100 pt-4">
                <button type="button" @click="closeModal()" class="text-sm font-semibold text-gray-500 border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" :disabled="saving"
                        :class="saving ? 'bg-gray-300 cursor-wait' : 'bg-[var(--color-brand-primary)] hover:bg-[var(--color-brand-secondary)]'"
                        class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition"
                        x-text="saving ? 'Menyimpan...' : '💾 Simpan'"></button>
            </div>
        </form>
    </div>

</div>

<script>
function productsPage() {
    return {
        products: [],
        loading: true,
        activeTab: 'all',
        isModalOpen: false,
        saving: false,
        form: { id: null, name: '', category: 'makanan', price: 0, type: 'pos', stock: 0 },
        stockSavedId: null,
        toast: { message: '', isError: false },
        uploading: false,
        uploadError: '',

        tabs: [
            { value: 'all', label: 'Semua', icon: '📦' },
            { value: 'makanan', label: 'Makanan', icon: '🍜' },
            { value: 'minuman', label: 'Minuman', icon: '🥤' },
            { value: 'souvenir', label: 'Souvenir', icon: '🎁' },
            { value: 'baju_merchandise', label: 'Baju', icon: '👕' },
            { value: 'produk_umkm', label: 'UMKM', icon: '☕' },
            { value: 'tiket_masuk', label: 'Tiket Masuk', icon: '🎫' },
        ],

        categoryOptions: [
            { value: 'tiket_masuk', label: 'Tiket Masuk' },
            { value: 'makanan', label: 'Makanan' },
            { value: 'minuman', label: 'Minuman' },
            { value: 'souvenir', label: 'Souvenir' },
            { value: 'baju_merchandise', label: 'Baju & Merchandise' },
            { value: 'produk_umkm', label: 'Produk UMKM' },
        ],

        get filteredProducts() {
            if (this.activeTab === 'all') return this.products;
            return this.products.filter(p => p.category === this.activeTab);
        },

        countByTab(tab) {
            if (tab === 'all') return this.products.length;
            return this.products.filter(p => p.category === tab).length;
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/admin/products', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.products = (data.data || []).map(p => ({ ...p, price: Number(p.price) }));
                // Set checkbox values for product selection
                const checkboxes = document.querySelectorAll('.checkbox-item');
                checkboxes.forEach((cb, index) => {
                    if (this.products[index]) {
                        cb.value = this.products[index].id;
                    }
                });
            } catch (e) {
                this.showToast('Gagal memuat produk.', true);
            } finally {
                this.loading = false;
            }
        },

        categoryEmoji(category) {
            return {
                tiket_masuk: '🎫', makanan: '🍜', minuman: '🥤',
                souvenir: '🎁', baju_merchandise: '👕', produk_umkm: '☕',
            }[category] ?? '🛍️';
        },

        categoryLabel(category) {
            return this.categoryOptions.find(c => c.value === category)?.label ?? category;
        },

        productHasHistory(product) {
            return (Number(product.addons_count) || 0) + (Number(product.pos_details_count) || 0) > 0;
        },

        onCategoryChange() {
            if (!this.form.id && this.form.category === 'tiket_masuk') {
                this.form.type = 'addon';
            }
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        openModal(product = null) {
            this.form = product
                ? { ...product, for_kasir_makanan: !!product.for_kasir_makanan, images: product.images ?? [] }
                : { id: null, name: '', category: 'makanan', price: 0, type: 'pos', stock: 0, for_kasir_makanan: true, images: [] };
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async save() {
            this.saving = true;
            const isEdit = !!this.form.id;
            const url = isEdit ? '/api/admin/products/' + this.form.id : '/api/admin/products';
            const method = isEdit ? 'PUT' : 'POST';
            const { images, ...payload } = this.form; // gambar dikelola terpisah via upload endpoint

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.ok) {
                    const product = { ...data.data, price: Number(data.data.price) };
                    this.products = isEdit
                        ? this.products.map(p => (p.id === product.id ? product : p))
                        : [product, ...this.products];
                    this.closeModal();
                    this.showToast('Produk tersimpan.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal menyimpan produk.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        async updateStock(product, value) {
            const stock = Math.max(0, parseInt(value) || 0);
            try {
                const res = await fetch('/api/admin/products/' + product.id + '/stock', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ stock }),
                });
                if (res.ok) {
                    product.stock = stock;
                    this.stockSavedId = product.id;
                    setTimeout(() => { this.stockSavedId = null; }, 1500);
                } else {
                    this.showToast('Gagal update stok.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        async uploadImages(event) {
            const files = event.target.files;

            if (!files || files.length === 0 || !this.form.id) {
                return;
            }

            this.uploading = true;
            this.uploadError = '';

            const formData = new FormData();
            Array.from(files).forEach(f => formData.append('images[]', f));

            try {
                const res = await fetch('/api/admin/products/' + this.form.id + '/images', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: formData,
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.form.images = data.data || this.form.images;
                    this.products = this.products.map(p => p.id === this.form.id ? { ...p, images: this.form.images } : p);
                    this.showToast('Foto terunggah.');
                } else {
                    this.uploadError = data.message ?? 'Gagal mengunggah foto.';
                }
            } catch (e) {
                this.uploadError = 'Koneksi gagal.';
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },

        async removeImage(img) {
            if (!confirm('Hapus foto ini?')) return;
            try {
                const res = await fetch('/api/admin/products/' + this.form.id + '/images/' + img.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.form.images = data.data || [];
                    this.products = this.products.map(p => p.id === this.form.id ? { ...p, images: this.form.images } : p);
                    this.showToast('Foto dihapus.');
                } else {
                    this.showToast(data.message ?? 'Gagal menghapus foto.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        async toggleForKasirMakanan(product) {
            const res = await fetch('/api/admin/products/' + product.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ for_kasir_makanan: !product.for_kasir_makanan }),
            });
            const data = await res.json();

            if (res.ok) {
                const updated = { ...data.data, price: Number(data.data.price) };
                this.products = this.products.map(p => (p.id === updated.id ? updated : p));
            } else {
                this.showToast(data.message ?? 'Gagal mengubah akses kasir makanan.', true);
            }
        },

        async destroy(product) {
            if (this.productHasHistory(product)) {
                this.showToast('Produk "' + product.name + '" punya riwayat transaksi dan tidak dapat dihapus.', true);
                return;
            }
            if (!confirm('Hapus produk "' + product.name + '"?')) return;
            try {
                const res = await fetch('/api/admin/products/' + product.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.products = this.products.filter(p => p.id !== product.id);
                    this.showToast('Produk dihapus.');
                } else {
                    this.showToast(data.message || 'Gagal menghapus produk.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        firstError(data) {
            if (data?.errors) {
                const first = Object.values(data.errors)[0];
                return Array.isArray(first) ? first[0] : String(first);
            }
            return data?.message ?? null;
        },

        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },

        selectAllProducts() {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            const selectAll = document.getElementById('select-all-products');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
        },
    };
}
</script>
@endsection

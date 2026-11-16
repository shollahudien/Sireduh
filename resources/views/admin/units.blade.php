@extends('layouts.admin')
@section('page-title', 'Unit Sewa')

@section('content')
<div x-data="unitsPage()" x-init="load()">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-[var(--color-error)]' : 'bg-[var(--color-brand-primary)]'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 flex items-center gap-3">
        <span x-text="toast.isError ? '⚠️' : '✅'"></span><span x-text="toast.message"></span>
    </div>

    {{-- Glamping package pricing --}}
    <div class="bg-white dark:bg-gray-900 dark:text-gray-100 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-200 overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">💰 Harga Paket Glamping</h2>
                <p class="text-xs text-gray-400">Atur paket & harga sewa glamping per jam/jadwal (dipakai online & kasir/POS).</p>
            </div>
            <button @click="openPackageModal()" class="bg-amber-600 text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-amber-700 transition shadow">
                + Tambah Paket
            </button>
        </div>
        <div class="overflow-x-auto -mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-4 sm:pl-6">Paket</th>
                        <th class="pb-3 pr-4">Kode</th>
                        <th class="pb-3 pr-4 text-center">Durasi</th>
                        <th class="pb-3 pr-4 text-right">Harga</th>
                        <th class="pb-3 pr-4 text-center">Aktif</th>
                        <th class="pb-3 pr-4 sm:pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="glampingPackages.length === 0">
                        <tr><td colspan="6" class="py-10 text-center text-gray-400">Belum ada paket glamping.</td></tr>
                    </template>
                    <template x-for="pkg in glampingPackages" :key="pkg.id">
                        <tr class="hover:bg-gray-50 align-middle">
                            <td class="py-3 pr-4 pl-4 sm:pl-6 font-semibold text-gray-800" x-text="pkg.name"></td>
                            <td class="py-3 pr-4 text-xs text-gray-500 font-mono" x-text="pkg.code"></td>
                            <td class="py-3 pr-4 text-center text-gray-700" x-text="pkg.duration_hours + ' jam'"></td>
                            <td class="py-3 pr-4 text-right font-semibold text-green-800" x-text="formatIDR(pkg.price)"></td>
                            <td class="py-3 pr-4 text-center">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="pkg.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'" x-text="pkg.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="py-3 pr-4 sm:pr-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openPackageModal(pkg)" class="text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition">✏️ Edit</button>
                                    <button @click="destroyPackage(pkg)" class="text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 text-[11px] text-gray-500 bg-amber-50/40">
            Harga paket glamping di sini dipakai untuk pemesanan online dan kasir (POS). Stok/kapasitas slot glamping mengikuti unit glamping di tabel unit (total 6 slot).
        </div>
    </div>

    {{-- Glamping package modal --}}
    <div x-show="isPackageModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closePackageModal()"></div>
        <form @submit.prevent="savePackage()" class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800" x-text="packageForm.id ? '✏️ Edit Paket Glamping' : '💰 Tambah Paket Glamping'"></h3>
                <button type="button" @click="closePackageModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
            </div>
            <div class="p-6 space-y-3">
                <div x-show="!packageForm.id">
                    <label class="text-xs font-semibold text-gray-500">Kode (unik, tanpa spasi)</label>
                    <input type="text" x-model="packageForm.code" :required="!packageForm.id" maxlength="50" placeholder="glamping_6jam"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Nama Paket</label>
                    <input type="text" x-model="packageForm.name" required maxlength="255" placeholder="Paket 6 Jam"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Durasi (jam)</label>
                        <input type="number" min="1" max="168" x-model.number="packageForm.duration_hours" required
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Harga (Rp)</label>
                        <input type="number" min="0" x-model.number="packageForm.price" required
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 items-end">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Urutan</label>
                        <input type="number" min="0" x-model.number="packageForm.sort_order"
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 pb-2">
                        <input type="checkbox" x-model="packageForm.is_active" class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                        Aktif (tampil di pemesanan)
                    </label>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center gap-2 justify-end border-t border-gray-100 pt-4">
                <button type="button" @click="closePackageModal()" class="text-sm font-semibold text-gray-500 border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" :disabled="packageSaving"
                        :class="packageSaving ? 'bg-gray-300 cursor-wait' : 'bg-amber-600 hover:bg-amber-700'"
                        class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition"
                        x-text="packageSaving ? 'Menyimpan...' : '💾 Simpan Paket'"></button>
            </div>
        </form>
    </div>

    {{-- Aula package pricing --}}
    <div class="bg-white dark:bg-gray-900 dark:text-gray-100 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-200 overflow-hidden mb-4">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">💰 Harga Paket Aula</h2>
                <p class="text-xs text-gray-400">Atur paket & harga sewa aula per jam/jadwal (dipakai online & kasir/POS).</p>
            </div>
            <button @click="openAulaPackageModal()" class="bg-blue-700 text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-blue-800 transition shadow">
                + Tambah Paket Aula
            </button>
        </div>
        <div class="overflow-x-auto -mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-4 sm:pl-6">Paket</th>
                        <th class="pb-3 pr-4">Kode</th>
                        <th class="pb-3 pr-4 text-center">Durasi</th>
                        <th class="pb-3 pr-4 text-right">Harga</th>
                        <th class="pb-3 pr-4 text-center">Aktif</th>
                        <th class="pb-3 pr-4 sm:pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="aulaPackages.length === 0">
                        <tr><td colspan="6" class="py-10 text-center text-gray-400">Belum ada paket aula.</td></tr>
                    </template>
                    <template x-for="pkg in aulaPackages" :key="pkg.id">
                        <tr class="hover:bg-gray-50 align-middle">
                            <td class="py-3 pr-4 pl-4 sm:pl-6 font-semibold text-gray-800" x-text="pkg.name"></td>
                            <td class="py-3 pr-4 text-xs text-gray-500 font-mono" x-text="pkg.code"></td>
                            <td class="py-3 pr-4 text-center text-gray-700" x-text="pkg.duration_hours + ' jam'"></td>
                            <td class="py-3 pr-4 text-right font-semibold text-green-800" x-text="formatIDR(pkg.price)"></td>
                            <td class="py-3 pr-4 text-center">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="pkg.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'" x-text="pkg.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="py-3 pr-4 sm:pr-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openAulaPackageModal(pkg)" class="text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition">✏️ Edit</button>
                                    <button @click="destroyAulaPackage(pkg)" class="text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 text-[11px] text-gray-500 bg-blue-50/40">
            Harga paket aula di sini dipakai untuk pemesanan online dan kasir (POS). Ketersediaan mengikuti kapasitas/slot unit aula per jadwal.
        </div>
    </div>

    {{-- Aula package modal --}}
    <div x-show="isAulaPackageModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAulaPackageModal()"></div>
        <form @submit.prevent="saveAulaPackage()" class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800" x-text="aulaPackageForm.id ? '✏️ Edit Paket Aula' : '💰 Tambah Paket Aula'"></h3>
                <button type="button" @click="closeAulaPackageModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
            </div>
            <div class="p-6 space-y-3">
                <div x-show="!aulaPackageForm.id">
                    <label class="text-xs font-semibold text-gray-500">Kode (unik, tanpa spasi)</label>
                    <input type="text" x-model="aulaPackageForm.code" :required="!aulaPackageForm.id" maxlength="50" placeholder="aula_seharian"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Nama Paket</label>
                    <input type="text" x-model="aulaPackageForm.name" required maxlength="255" placeholder="Paket Seharian (8 Jam)"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Durasi (jam)</label>
                        <input type="number" min="1" max="168" x-model.number="aulaPackageForm.duration_hours" required
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Harga (Rp)</label>
                        <input type="number" min="0" x-model.number="aulaPackageForm.price" required
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 items-end">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Urutan</label>
                        <input type="number" min="0" x-model.number="aulaPackageForm.sort_order"
                               class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 pb-2">
                        <input type="checkbox" x-model="aulaPackageForm.is_active" class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                        Aktif (tampil di pemesanan)
                    </label>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center gap-2 justify-end border-t border-gray-100 pt-4">
                <button type="button" @click="closeAulaPackageModal()" class="text-sm font-semibold text-gray-500 border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" :disabled="aulaPackageSaving"
                        :class="aulaPackageSaving ? 'bg-gray-300 cursor-wait' : 'bg-blue-700 hover:bg-blue-800'"
                        class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition"
                        x-text="aulaPackageSaving ? 'Menyimpan...' : '💾 Simpan Paket'"></button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-900 dark:text-gray-100 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">🏕️ Unit Sewa</h2>
                <p class="text-xs text-gray-400">Glamping, Aula, dan Alat Wisata — kelola harga &amp; stok.</p>
            </div>
            <div class="flex items-center gap-2">
                <select x-model="filterType" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                    <option value="">Semua Tipe</option>
                    <option value="glamping">Glamping</option>
                    <option value="aula">Aula</option>
                    <option value="alat_wisata">Alat Wisata</option>
                </select>
                <button @click="openModal()" class="bg-[var(--color-brand-primary)] text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-[var(--color-brand-secondary)] transition shadow">
                    + Tambah Unit
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto -mx-0">
            <table class="rtable w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 dark:text-gray-300 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-4 sm:pl-6">Nama</th>
                        <th class="pb-3 pr-4">Tipe</th>
                        <th class="pb-3 pr-4 text-right">Harga</th>
                        <th class="pb-3 pr-4 text-center">Kapasitas</th>
                        <th class="pb-3 pr-4">Stok</th>
                        <th class="pb-3 pr-4">Deskripsi</th>
                        <th class="pb-3 pr-4 sm:pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-16 text-center text-gray-400">Memuat unit...</td></tr>
                    </template>
                    <template x-if="!loading && filteredUnits.length === 0">
                        <tr><td colspan="7" class="py-16 text-center text-gray-400">Belum ada unit.</td></tr>
                    </template>
                    <template x-for="unit in filteredUnits" :key="unit.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 align-middle">
                            <td data-label="Nama" class="py-3 pr-4 pl-4 sm:pl-6 dark:text-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img x-show="unit.images?.length" :src="unit.images?.[0]?.url" :alt="unit.name" class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg object-cover border border-gray-100 dark:border-gray-200 shrink-0" x-cloak>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200 leading-snug break-words" x-text="unit.name"></span>
                                </div>
                            </td>
                            <td data-label="Tipe" class="py-3 pr-4">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="typeBadge(unit.type)" x-text="typeLabel(unit.type)"></span>
                            </td>
                            <td data-label="Harga / Malam" class="py-3 pr-4 text-right font-semibold text-green-800 dark:text-green-300" x-text="formatIDR(unit.price)"></td>
                            <td data-label="Kapasitas" class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300" x-text="unit.capacity + ' org'"></td>
                            <td data-label="Stok" class="py-3 pr-4">
                                <div class="flex items-center gap-1.5">
                                    <input type="number" min="0" :value="unit.stock"
                                           @keydown.enter="$event.target.blur()"
                                           @change="updateStock(unit, $event.target.value)"
                                           class="w-16 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500">
                                    <span class="text-xs text-green-600 font-bold" x-show="stockSavedId === unit.id" x-cloak>✓</span>
                                </div>
                            </td>
                            <td data-label="Deskripsi" class="py-3 pr-4 text-xs text-gray-500 max-w-56 truncate" :title="unit.description" x-text="unit.description ?? '—'"></td>
                            <td class="py-3 pr-4 sm:pr-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openModal(unit)" class="focus-visible:ring-2 focus-visible:ring-lime-300 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition">✏️ Edit</button>
                                    <button @click="destroy(unit)"
                                            :disabled="unitHasHistory(unit)"
                                            :title="unitHasHistory(unit) ? 'Punya riwayat sewa/transaksi — tidak bisa dihapus' : 'Hapus unit'"
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
                <h3 class="font-bold text-gray-800" x-text="form.id ? '✏️ Edit Unit' : '🏕️ Tambah Unit'"></h3>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none" aria-label="Tutup modal">×</button>
            </div>
            <div class="p-6 space-y-3 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="text-xs font-semibold text-gray-500">Nama Unit</label>
                    <input type="text" x-model="form.name" required maxlength="255" placeholder="Tenda Premium Glamping"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Tipe</label>
                        <select x-model="form.type" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="glamping">Glamping</option>
                            <option value="aula">Aula</option>
                            <option value="alat_wisata">Alat Wisata</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Kapasitas</label>
                        <input type="number" min="1" x-model.number="form.capacity" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500" x-text="form.type === 'aula' ? 'Harga Sewa Aula (Rp)' : (form.type === 'glamping' ? 'Harga Referensi Glamping (Rp)' : 'Harga / Malam (Rp)')"></label>
                        <input type="number" min="0" x-model.number="form.price" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Stok</label>
                        <input type="number" min="0" x-model.number="form.stock" required class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Deskripsi</label>
                    <textarea x-model="form.description" rows="2" placeholder="Fasilitas, ketentuan sewa..."
                              class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600 text-sm"></textarea>
                </div>

                {{-- Image gallery --}}
                <div x-show="form.id">
                    <label class="text-xs font-semibold text-gray-500">Foto Unit <span class="text-gray-400">(klien bisa lihat galeri ini)</span></label>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        <template x-for="(img, i) in form.images" :key="img.id">
                            <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 group">
                                <img :src="img.url" loading="lazy" :alt="img.alt || 'foto unit'" class="w-full h-full object-cover">
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
                    💾 Simpan unit dulu untuk menambahkan foto.
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
function unitsPage() {
    return {
        units: [],
        glampingPackages: [],
        aulaPackages: [],
        loading: true,
        filterType: '',
        isModalOpen: false,
        isPackageModalOpen: false,
        isAulaPackageModalOpen: false,
        saving: false,
        packageSaving: false,
        aulaPackageSaving: false,
        form: { id: null, name: '', type: 'glamping', price: 0, capacity: 1, stock: 1, description: '' },
        packageForm: { id: null, code: '', name: '', duration_hours: 6, price: 0, is_active: true, sort_order: 0 },
        aulaPackageForm: { id: null, code: '', name: '', duration_hours: 4, price: 0, is_active: true, sort_order: 0 },
        stockSavedId: null,
        toast: { message: '', isError: false },
        uploading: false,
        uploadError: '',

        get filteredUnits() {
            if (!this.filterType) return this.units;
            return this.units.filter(u => u.type === this.filterType);
        },

        async load() {
            this.loading = true;
            try {
                const [unitsRes, pkgRes, aulaPkgRes] = await Promise.all([
                    fetch('/api/admin/units', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/admin/glamping-packages', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/admin/aula-packages', { headers: { 'Accept': 'application/json' } }),
                ]);
                const data = await unitsRes.json();
                this.units = (data.data || []).map(u => ({ ...u, price: Number(u.price) }));
                try {
                    const pkgData = await pkgRes.json();
                    this.glampingPackages = (pkgData.data || []).map(p => ({ ...p, price: Number(p.price) }));
                } catch (e) { this.glampingPackages = []; }
                try {
                    const aulaData = await aulaPkgRes.json();
                    this.aulaPackages = (aulaData.data || []).map(p => ({ ...p, price: Number(p.price) }));
                } catch (e) { this.aulaPackages = []; }
                const checkboxes = document.querySelectorAll('.checkbox-item');
                checkboxes.forEach((cb, index) => {
                    if (this.units[index]) cb.value = this.units[index].id;
                });
            } catch (e) {
                this.showToast('Gagal memuat data unit/paket.', true);
            } finally {
                this.loading = false;
            }
        },

        typeLabel(type) {
            return { glamping: 'Glamping', aula: 'Aula', alat_wisata: 'Alat Wisata' }[type] ?? type;
        },

        typeBadge(type) {
            return {
                glamping: 'bg-green-100 text-green-800',
                aula: 'bg-blue-100 text-blue-800',
                alat_wisata: 'bg-amber-100 text-amber-800',
            }[type] ?? 'bg-gray-100 text-gray-600';
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        openModal(unit = null) {
            this.form = unit
                ? { ...unit, description: unit.description ?? '', images: unit.images ?? [] }
                : { id: null, name: '', type: 'glamping', price: 0, capacity: 1, stock: 1, description: '', images: [] };
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async save() {
            this.saving = true;
            const isEdit = !!this.form.id;
            const url = isEdit ? '/api/admin/units/' + this.form.id : '/api/admin/units';
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
                    const unit = { ...data.data, price: Number(data.data.price) };
                    this.units = isEdit
                        ? this.units.map(u => (u.id === unit.id ? unit : u))
                        : [unit, ...this.units];
                    this.closeModal();
                    this.showToast('Unit tersimpan.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal menyimpan unit.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        async updateStock(unit, value) {
            const stock = Math.max(0, parseInt(value) || 0);
            try {
                const res = await fetch('/api/admin/units/' + unit.id + '/stock', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ stock }),
                });
                if (res.ok) {
                    unit.stock = stock;
                    this.stockSavedId = unit.id;
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
                const res = await fetch('/api/admin/units/' + this.form.id + '/images', {
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
                    this.units = this.units.map(u => u.id === this.form.id ? { ...u, images: this.form.images } : u);
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
                const res = await fetch('/api/admin/units/' + this.form.id + '/images/' + img.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.form.images = data.data || [];
                    this.units = this.units.map(u => u.id === this.form.id ? { ...u, images: this.form.images } : u);
                    this.showToast('Foto dihapus.');
                } else {
                    this.showToast(data.message ?? 'Gagal menghapus foto.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        async destroy(unit) {
            if (this.unitHasHistory(unit)) {
                this.showToast('Unit "' + unit.name + '" punya riwayat sewa/transaksi dan tidak dapat dihapus.', true);
                return;
            }
            if (!confirm('Hapus unit "' + unit.name + '"?')) return;
            try {
                const res = await fetch('/api/admin/units/' + unit.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.units = this.units.filter(u => u.id !== unit.id);
                    this.showToast('Unit dihapus.');
                } else {
                    this.showToast(data.message || 'Gagal menghapus unit.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        unitHasHistory(unit) {
            return (Number(unit.reservations_count) || 0) + (Number(unit.pos_transaction_details_count) || 0) > 0;
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

        openPackageModal(pkg = null) {
            this.packageForm = pkg
                ? { id: pkg.id, code: pkg.code, name: pkg.name, duration_hours: pkg.duration_hours, price: Number(pkg.price), is_active: !!pkg.is_active, sort_order: pkg.sort_order || 0 }
                : { id: null, code: '', name: '', duration_hours: 6, price: 0, is_active: true, sort_order: 0 };
            this.isPackageModalOpen = true;
        },

        closePackageModal() {
            this.isPackageModalOpen = false;
        },

        async savePackage() {
            this.packageSaving = true;
            const isEdit = !!this.packageForm.id;
            const url = isEdit ? '/api/admin/glamping-packages/' + this.packageForm.id : '/api/admin/glamping-packages';
            const method = isEdit ? 'PUT' : 'POST';
            const payload = { ...this.packageForm };
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
                    const pkg = { ...data.data, price: Number(data.data.price) };
                    this.glampingPackages = isEdit
                        ? this.glampingPackages.map(p => p.id === pkg.id ? pkg : p)
                        : [...this.glampingPackages, pkg].sort((a,b) => (a.sort_order||0)-(b.sort_order||0));
                    this.closePackageModal();
                    this.showToast('Paket glamping tersimpan.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal menyimpan paket.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.packageSaving = false;
            }
        },

        async destroyPackage(pkg) {
            if (!confirm('Hapus paket "' + pkg.name + '"?')) return;
            try {
                const res = await fetch('/api/admin/glamping-packages/' + pkg.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.glampingPackages = this.glampingPackages.filter(p => p.id !== pkg.id);
                    this.showToast('Paket glamping dihapus.');
                } else {
                    this.showToast(data.message || 'Gagal menghapus paket.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        openAulaPackageModal(pkg = null) {
            this.aulaPackageForm = pkg
                ? { id: pkg.id, code: pkg.code, name: pkg.name, duration_hours: pkg.duration_hours, price: Number(pkg.price), is_active: !!pkg.is_active, sort_order: pkg.sort_order || 0 }
                : { id: null, code: '', name: '', duration_hours: 4, price: 0, is_active: true, sort_order: 0 };
            this.isAulaPackageModalOpen = true;
        },

        closeAulaPackageModal() {
            this.isAulaPackageModalOpen = false;
        },

        async saveAulaPackage() {
            this.aulaPackageSaving = true;
            const isEdit = !!this.aulaPackageForm.id;
            const url = isEdit ? '/api/admin/aula-packages/' + this.aulaPackageForm.id : '/api/admin/aula-packages';
            const method = isEdit ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ ...this.aulaPackageForm }),
                });
                const data = await res.json();
                if (res.ok) {
                    const pkg = { ...data.data, price: Number(data.data.price) };
                    this.aulaPackages = isEdit
                        ? this.aulaPackages.map(p => p.id === pkg.id ? pkg : p)
                        : [...this.aulaPackages, pkg].sort((a,b) => (a.sort_order||0)-(b.sort_order||0));
                    this.closeAulaPackageModal();
                    this.showToast('Paket aula tersimpan.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal menyimpan paket aula.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.aulaPackageSaving = false;
            }
        },

        async destroyAulaPackage(pkg) {
            if (!confirm('Hapus paket "' + pkg.name + '"?')) return;
            try {
                const res = await fetch('/api/admin/aula-packages/' + pkg.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.aulaPackages = this.aulaPackages.filter(p => p.id !== pkg.id);
                    this.showToast('Paket aula dihapus.');
                } else {
                    this.showToast(data.message || 'Gagal menghapus paket aula.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        selectAllUnits() {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            const selectAll = document.getElementById('select-all-units');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
        },
    };
}
</script>
@endsection

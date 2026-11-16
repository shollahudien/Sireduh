<!-- Action Buttons Component -->
<!-- Gunakan seperti: @include('components.action-buttons', ['product' => $product]) -->
<div class="flex items-center justify-end gap-1.5">
    <button @click="openModal(product)" class="text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition focus-visible:ring-2 focus-visible:ring-lime-300">✏️ Edit</button>
    <button @click="destroy(product)"
            :disabled="productHasHistory(product)"
            :title="productHasHistory(product) ? 'Punya riwayat transaksi — tidak bisa dihapus' : 'Hapus produk'"
            class="text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition focus-visible:ring-2 focus-visible:ring-lime-300 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50">🗑️</button>
</div>
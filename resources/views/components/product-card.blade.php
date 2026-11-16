<!-- Product Card Component -->
<!-- Gunakan seperti: @include('components.product-card', ['product' => $product]) -->
<div class="group hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
    <div class="flex items-center gap-2.5">
        <img x-show="product.images?.length" :src="product.images?.[0]?.url" :alt="product.name" class="w-12 h-12 rounded-lg object-cover border border-gray-100 dark:border-gray-200 shrink-0" x-cloak>
        <span class="text-2xl" x-show="!product.images?.length" x-cloak x-text="categoryEmoji(product.category)"></span>
        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="product.name"></span>
    </div>
</div>
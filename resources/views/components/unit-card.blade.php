<!-- Unit Card Component -->
<!-- Gunakan seperti: @include('components.unit-card', ['unit' => $unit]) -->
<div class="group hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
    <div class="flex items-center gap-3">
        <img x-show="unit.images?.length" :src="unit.images?.[0]?.url" :alt="unit.name" class="w-12 h-12 rounded-lg object-cover border border-gray-100 dark:border-gray-200 shrink-0" x-cloak>
        <span class="font-semibold text-gray-800 dark:text-gray-200 leading-snug" x-text="unit.name"></span>
    </div>
</div>
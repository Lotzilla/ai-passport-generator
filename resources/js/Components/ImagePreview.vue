<template>
  <div class="flex flex-col items-center gap-4">
    <!-- Image display -->
    <div class="relative rounded-xl overflow-hidden shadow-md border border-gray-200 bg-white"
         :style="{ width: '240px', height: '240px' }">
      <!-- Loading overlay -->
      <div v-if="loading"
           class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10">
        <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <p class="mt-2 text-xs text-gray-500">Processing…</p>
      </div>

      <img
        v-if="src"
        :src="src"
        alt="Preview"
        class="w-full h-full object-cover"
        :class="{ 'opacity-30': loading }"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
        <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
          <circle cx="8.5" cy="8.5" r="1.5" stroke-width="1.5"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="m21 15-5-5L5 21"/>
        </svg>
      </div>
    </div>

    <!-- Label -->
    <p v-if="label" class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ label }}</p>

    <!-- Download button -->
    <a
      v-if="downloadUrl"
      :href="downloadUrl"
      target="_blank"
      class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v4m0 0-3-3m3 3 3-3M12 4v8"/>
      </svg>
      Download Photo
    </a>
  </div>
</template>

<script setup>
defineProps({
  src:         { type: String, default: null },
  label:       { type: String, default: '' },
  loading:     { type: Boolean, default: false },
  downloadUrl: { type: String, default: null },
})
</script>

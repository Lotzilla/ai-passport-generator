<template>
  <div>
    <label
      class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-2xl cursor-pointer transition-colors"
      :class="
        isDragging
          ? 'border-indigo-500 bg-indigo-50'
          : 'border-gray-300 bg-white hover:border-indigo-400 hover:bg-indigo-50'
      "
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/jpeg,image/png"
        class="hidden"
        @change="onFileChange"
      />

      <div class="flex flex-col items-center gap-2 text-center px-4">
        <!-- Icon -->
        <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M12 16v-8m0 0-3 3m3-3 3 3M6.5 19A4.5 4.5 0 012 14.5c0-2.28 1.7-4.17 3.92-4.45A5.5 5.5 0 0112 5a5.5 5.5 0 015.5 5.5c0 .17-.01.34-.02.5A3.5 3.5 0 0122 14.5 3.5 3.5 0 0118.5 18" />
        </svg>

        <p class="text-sm font-medium text-gray-700">
          <span class="text-indigo-600">Click to upload</span> or drag &amp; drop
        </p>
        <p class="text-xs text-gray-400">JPG or PNG · Max 10 MB</p>
      </div>
    </label>

    <!-- Error message -->
    <p v-if="fileError" class="mt-2 text-xs text-red-500">{{ fileError }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['file-selected'])

const isDragging = ref(false)
const fileError  = ref('')
const fileInput  = ref(null)

const ALLOWED_TYPES = ['image/jpeg', 'image/png']
const MAX_BYTES     = 10 * 1024 * 1024  // 10 MB

function onFileChange(event) {
  const file = event.target.files?.[0]
  if (file) validateAndEmit(file)
}

function onDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files?.[0]
  if (file) validateAndEmit(file)
}

function validateAndEmit(file) {
  fileError.value = ''

  if (!ALLOWED_TYPES.includes(file.type)) {
    fileError.value = 'Only JPG and PNG images are accepted.'
    return
  }
  if (file.size > MAX_BYTES) {
    fileError.value = 'File size must not exceed 10 MB.'
    return
  }

  emit('file-selected', file)
}
</script>

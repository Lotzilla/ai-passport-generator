<template>
  <div ref="scrollRef" class="flex-1 overflow-y-auto px-4 py-6 space-y-5">
    <transition-group name="msg" tag="div" class="space-y-5">

      <div
        v-for="msg in messages"
        :key="msg.id"
        class="flex items-end gap-3"
        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
      >
        <!-- Bot avatar -->
        <div v-if="msg.role === 'bot'" class="flex-shrink-0 mb-0.5">
          <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-md">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9.75 3.75a6 6 0 016.08 5.254A4.5 4.5 0 0119.5 13.5h-15a4.5 4.5 0 013.67-4.496A6 6 0 019.75 3.75z"/>
            </svg>
          </div>
        </div>

        <!-- Message bubble -->
        <div class="max-w-[78%] flex flex-col gap-2">

          <!-- Plain text -->
          <div
            v-if="msg.type === 'text' || !msg.type"
            class="px-4 py-3 rounded-2xl text-sm leading-relaxed whitespace-pre-line"
            :class="msg.role === 'bot'
              ? 'bg-white border border-gray-100 text-gray-800 rounded-bl-sm shadow-sm'
              : 'bg-indigo-600 text-white rounded-br-sm'"
          >{{ msg.text }}</div>

          <!-- Image preview card (user upload) -->
          <div v-if="msg.type === 'image-upload'" class="rounded-2xl overflow-hidden border border-indigo-200 shadow-sm bg-white max-w-[200px]">
            <img :src="msg.src" class="w-full object-cover" alt="Your photo" />
            <div class="px-3 py-2 text-xs text-gray-500 bg-gray-50 border-t border-gray-100">{{ msg.filename }}</div>
          </div>

          <!-- Pipeline progress card -->
          <div v-if="msg.type === 'pipeline'" class="bg-white border border-gray-100 rounded-2xl rounded-bl-sm shadow-sm px-4 py-3 min-w-[220px]">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Processing pipeline</p>
            <div class="space-y-2">
              <div v-for="step in msg.steps" :key="step.id" class="flex items-center gap-2.5 text-xs">
                <!-- Done -->
                <span v-if="step.state === 'done'" class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </span>
                <!-- Active -->
                <span v-else-if="step.state === 'active'" class="w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                  </svg>
                </span>
                <!-- Pending -->
                <span v-else class="w-5 h-5 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0 text-gray-400 font-bold">
                  {{ step.id }}
                </span>
                <span :class="{
                  'text-gray-800 font-medium': step.state === 'active',
                  'text-gray-500': step.state === 'done',
                  'text-gray-300': step.state === 'pending',
                }">{{ step.label }}</span>
              </div>
            </div>
          </div>

          <!-- Result card (success) -->
          <div v-if="msg.type === 'result-success'" class="bg-white border border-green-200 rounded-2xl rounded-bl-sm shadow-sm overflow-hidden max-w-[240px]">
            <div class="bg-green-50 px-4 py-2.5 flex items-center gap-2 border-b border-green-100">
              <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
              <span class="text-xs font-semibold text-green-700">Passport Photo Ready</span>
            </div>
            <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100">
              <div class="flex flex-col items-center p-2">
                <p class="text-[10px] text-gray-400 mb-1.5 uppercase tracking-wide">Before</p>
                <img :src="msg.original" class="w-20 h-20 object-cover rounded-lg border border-gray-200" alt="Original" />
              </div>
              <div class="flex flex-col items-center p-2">
                <p class="text-[10px] text-gray-400 mb-1.5 uppercase tracking-wide">After</p>
                <img :src="msg.processed" class="w-20 h-20 object-cover rounded-lg border border-green-200" alt="Processed" />
              </div>
            </div>
            <div class="p-3 border-t border-gray-100">
              <a
                :href="msg.downloadUrl"
                target="_blank"
                class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-2 rounded-lg transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v4m0 0-3-3m3 3 3-3M12 4v8"/>
                </svg>
                Download Photo
              </a>
            </div>
          </div>

          <!-- Result card (error) -->
          <div v-if="msg.type === 'result-error'" class="bg-white border border-red-200 rounded-2xl rounded-bl-sm shadow-sm overflow-hidden">
            <div class="bg-red-50 px-4 py-2.5 flex items-center gap-2 border-b border-red-100">
              <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <span class="text-xs font-semibold text-red-700">Issues Found</span>
            </div>
            <ul class="px-4 py-3 space-y-1.5">
              <li v-for="err in msg.errors" :key="err" class="flex items-start gap-2 text-xs text-gray-700">
                <span class="mt-0.5 w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                {{ err }}
              </li>
            </ul>
          </div>

        </div>
      </div>

    </transition-group>

    <!-- Typing indicator -->
    <div v-if="typing" class="flex items-end gap-3">
      <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-md flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9.75 3.75a6 6 0 016.08 5.254A4.5 4.5 0 0119.5 13.5h-15a4.5 4.5 0 013.67-4.496A6 6 0 019.75 3.75z"/>
        </svg>
      </div>
      <div class="bg-white border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
        <div class="flex space-x-1.5 items-center h-4">
          <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0ms"></span>
          <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:150ms"></span>
          <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:300ms"></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'

defineProps({
  messages: { type: Array, required: true },
  typing:   { type: Boolean, default: false },
})

const scrollRef = ref(null)

watch(
  () => [scrollRef.value, scrollRef.value?.scrollHeight],
  async () => {
    await nextTick()
    if (scrollRef.value) scrollRef.value.scrollTop = scrollRef.value.scrollHeight
  },
  { immediate: true }
)

// Also scroll when messages change via a separate watcher
defineExpose({ scrollToBottom: async () => {
  await nextTick()
  if (scrollRef.value) scrollRef.value.scrollTop = scrollRef.value.scrollHeight
}})
</script>

<style scoped>
.msg-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.msg-enter-from   { opacity: 0; transform: translateY(12px) scale(0.97); }
</style>

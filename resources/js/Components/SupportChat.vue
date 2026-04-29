<template>
  <!-- Fixed to bottom-right corner, above everything -->
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

    <!-- ── Chat popup panel ────────────────────────────────────────────── -->
    <transition name="chat-popup">
      <div
        v-if="isOpen"
        class="bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden"
        style="width: 340px; height: 480px;"
      >
        <!-- Header -->
        <div class="flex-shrink-0 bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white leading-none">Support Assistant</p>
            <p class="text-xs text-indigo-200 mt-0.5">Ask me anything</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            <button
              @click="isOpen = false"
              class="text-white/70 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10"
              aria-label="Close chat"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages scroll area -->
        <div ref="scrollRef" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">

          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex items-end gap-2"
            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <!-- Bot avatar -->
            <div v-if="msg.role === 'bot'" class="flex-shrink-0 mb-0.5">
              <div class="w-7 h-7 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                </svg>
              </div>
            </div>

            <!-- Bubble -->
            <div
              class="max-w-[80%] px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-line"
              :class="msg.role === 'bot'
                ? 'bg-white border border-gray-100 text-gray-800 rounded-bl-sm shadow-sm'
                : 'bg-indigo-600 text-white rounded-br-sm'"
            >{{ msg.text }}</div>
          </div>

          <!-- Typing indicator -->
          <div v-if="loading" class="flex items-end gap-2">
            <div class="w-7 h-7 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
              </svg>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl rounded-bl-sm px-4 py-2.5 shadow-sm">
              <div class="flex space-x-1.5 items-center h-4">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0ms"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:150ms"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:300ms"></span>
              </div>
            </div>
          </div>

        </div>

        <!-- Quick-reply chips (shown only when idle) -->
        <div v-if="messages.length <= 2 && !loading" class="flex-shrink-0 px-3 py-2 flex flex-wrap gap-1.5 bg-gray-50 border-t border-gray-100">
          <button
            v-for="chip in quickReplies"
            :key="chip"
            @click="sendQuick(chip)"
            class="text-xs bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 text-gray-600 rounded-full px-3 py-1 transition-colors"
          >
            {{ chip }}
          </button>
        </div>

        <!-- Input bar -->
        <div class="flex-shrink-0 border-t border-gray-200 bg-white px-3 py-3 flex items-end gap-2">
          <textarea
            ref="inputRef"
            v-model="input"
            @keydown.enter.exact.prevent="sendMessage"
            placeholder="Type a message…"
            rows="1"
            maxlength="500"
            class="flex-1 resize-none rounded-xl border border-gray-200 bg-gray-50 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-colors leading-relaxed"
            style="max-height: 96px; overflow-y: auto;"
          ></textarea>
          <button
            @click="sendMessage"
            :disabled="!input.trim() || loading"
            class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
            :class="input.trim() && !loading
              ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-100'
              : 'bg-gray-100 text-gray-300 cursor-not-allowed'"
            aria-label="Send message"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
          </button>
        </div>

      </div>
    </transition>

    <!-- ── FAB toggle button ────────────────────────────────────────────── -->
    <button
      @click="toggleChat"
      class="w-14 h-14 rounded-full shadow-xl flex items-center justify-center transition-all duration-200 relative"
      :class="isOpen
        ? 'bg-gray-700 hover:bg-gray-800'
        : 'bg-indigo-600 hover:bg-indigo-700 hover:scale-105'"
      aria-label="Toggle support chat"
    >
      <!-- Unread badge -->
      <span
        v-if="!isOpen && unread > 0"
        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
      >{{ unread }}</span>

      <!-- Chat icon (closed state) -->
      <svg v-if="!isOpen" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
      </svg>
      <!-- Close icon (open state) -->
      <svg v-else class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>

  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import axios from 'axios'

// ── Props (live app state from Home.vue) ────────────────────────────────────
const props = defineProps({
  appStatus:      { type: String,  default: 'idle' },
  country:        { type: String,  default: 'US'   },
  pipelineErrors: { type: Array,   default: () => [] },
})

// ── State ──────────────────────────────────────────────────────────────────
const isOpen    = ref(false)
const input     = ref('')
const loading   = ref(false)
const messages  = ref([])
const unread    = ref(0)
const scrollRef = ref(null)
const inputRef  = ref(null)

// ── Context-aware quick replies ─────────────────────────────────────────────
const quickReplies = computed(() => {
  switch (props.appStatus) {
    case 'error':
      return ['Why did my photo fail?', 'How do I fix the background?', 'Lighting tips', 'Can I use glasses?']
    case 'success':
      return ['Can I print this?', 'Is this officially accepted?', 'What country specs?', 'How does it work?']
    case 'processing':
      return ['How long does it take?', 'What are the 7 agents?', 'Is it free?']
    default:
      return ['How does it work?', 'Photo tips', 'What countries?', 'Is it free?']
  }
})

// ── Helpers ─────────────────────────────────────────────────────────────────
function uid() { return Date.now() + Math.random() }

function addMessage(role, text) {
  messages.value.push({ id: uid(), role, text })
  scrollDown()
  if (role === 'bot' && !isOpen.value) unread.value++
}

async function scrollDown() {
  await nextTick()
  if (scrollRef.value) scrollRef.value.scrollTop = scrollRef.value.scrollHeight
}

// ── Build proactive messages from pipeline data ──────────────────────────────
function buildErrorGuidance() {
  if (!props.pipelineErrors?.length) {
    return "I noticed your photo didn't pass. No worries — let's fix it!\n\nCommon causes:\n• Face too small or too far from camera\n• Background isn't plain white\n• Lighting has shadows\n\nWant to tell me more about what happened?"
  }
  const fixes = props.pipelineErrors.map(e => `• ${e}`).join('\n')
  return `I can see exactly what happened with your photo. Here's what needs to be fixed:\n\n${fixes}\n\nWould you like step-by-step guidance on any of these?`
}

function buildSuccessMessage() {
  const countryNames = { US: 'United States', GB: 'United Kingdom', EU: 'EU/Schengen', CA: 'Canada', AU: 'Australia' }
  const label = countryNames[props.country] ?? props.country
  return `Your photo meets all official ${label} passport requirements ✅\n\nClick the green Download button to save it — it's ready to print or submit!`
}

// ── Proactive bot responses on app state changes ─────────────────────────────
let prevStatus = 'idle'
watch(() => props.appStatus, (newStatus) => {
  if (newStatus === 'error' && prevStatus === 'processing') {
    // Auto-open chat and give specific error guidance
    isOpen.value = true
    unread.value = 0
    setTimeout(() => addMessage('bot', buildErrorGuidance()), 400)
  } else if (newStatus === 'success' && prevStatus === 'processing') {
    // Add a success message (badge bump if closed)
    setTimeout(() => addMessage('bot', buildSuccessMessage()), 600)
  }
  prevStatus = newStatus
})

// ── Open / close ─────────────────────────────────────────────────────────────
function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    unread.value = 0
    nextTick(() => inputRef.value?.focus())
  }
}

// ── Send message ─────────────────────────────────────────────────────────────
async function sendMessage() {
  const text = input.value.trim()
  if (!text || loading.value) return

  input.value = ''
  addMessage('user', text)
  loading.value = true

  // Build conversation history for context (exclude the opening greeting)
  const history = messages.value
    .slice(0, -1)
    .filter(m => m.id !== messages.value[0]?.id)
    .map(m => ({
      role:    m.role === 'bot' ? 'assistant' : 'user',
      content: m.text,
    }))

  // Pass live app context with every request
  const context = {
    app_status: props.appStatus,
    country:    props.country,
    errors:     props.pipelineErrors ?? [],
  }

  try {
    const { data } = await axios.post('/support-chat', { message: text, history, context })
    addMessage('bot', data.reply)
  } catch {
    addMessage('bot', "Sorry, I'm having trouble connecting right now. Please try again in a moment.")
  } finally {
    loading.value = false
    nextTick(() => inputRef.value?.focus())
  }
}

function sendQuick(text) {
  input.value = text
  sendMessage()
}

// ── Boot ─────────────────────────────────────────────────────────────────────
onMounted(() => {
  setTimeout(() => {
    addMessage('bot', "Hi! 👋 I'm your passport photo assistant. Select your country, upload a selfie, and I'll guide you through every step. Got questions? Just ask!")
  }, 800)
})
</script>

<style scoped>
.chat-popup-enter-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-popup-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.95);
}
.chat-popup-leave-active {
  transition: all 0.18s ease;
}
.chat-popup-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.96);
}
</style>

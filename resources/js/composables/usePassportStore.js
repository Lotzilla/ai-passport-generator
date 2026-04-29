import { reactive } from 'vue'

/**
 * Central state store for the passport photo pipeline.
 * Shared across all components via provide/inject or direct import.
 */
export function usePassportStore() {
    const state = reactive({
        /** idle | uploaded | processing | success | error */
        status: 'idle',
        uploadedFilename: null,
        processedImage: null,
        downloadUrl: null,
        errors: [],
        messages: [],
        country: 'US',
    })

    function addBotMessage(text) {
        state.messages.push({ role: 'bot', text, id: Date.now() + Math.random() })
    }

    function addUserMessage(text) {
        state.messages.push({ role: 'user', text, id: Date.now() + Math.random() })
    }

    function reset() {
        state.status = 'idle'
        state.uploadedFilename = null
        state.processedImage = null
        state.downloadUrl = null
        state.errors = []
        state.messages = []
    }

    return { state, addBotMessage, addUserMessage, reset }
}

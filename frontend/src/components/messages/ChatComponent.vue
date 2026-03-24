<template>
  <div class="flex h-full min-h-0 flex-col">
    <div ref="threadRef" class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
      <div
        v-for="message in messages"
        :key="String(message.id)"
        :class="[
          'mb-2 flex',
          Number(message.sender?.id) === currentUserId ? 'justify-end' : 'justify-start',
        ]"
      >
        <div
          :class="[
            'max-w-[85%] rounded-2xl px-4 py-2 text-sm shadow-sm md:max-w-lg',
            Number(message.sender?.id) === currentUserId
              ? 'rounded-br-sm bg-indigo-600 text-white'
              : 'rounded-bl-sm border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
          ]"
        >
          <p>{{ message.body }}</p>
          <p
            :class="[
              'mt-1 flex items-center justify-end gap-1 text-[11px]',
              Number(message.sender?.id) === currentUserId
                ? 'text-indigo-200'
                : 'text-slate-400 dark:text-slate-500',
            ]"
          >
            <span>{{ formatTime(message.created_at) }}</span>
            <span
              v-if="Number(message.sender?.id) === currentUserId"
              :class="[
                'font-semibold tracking-tight',
                message.read_at ? 'text-sky-300' : 'text-indigo-200',
              ]"
            >
              {{ message.read_at ? '✓✓' : message.optimistic ? '⏳' : '✓' }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <div v-if="typingNames.length" class="px-4 pb-2 text-xs text-slate-500 dark:text-slate-400">
      {{ typingText }}
    </div>

    <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
      <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-800">
        <textarea
          ref="textareaRef"
          v-model="body"
          rows="1"
          :disabled="sending"
          placeholder="Type a message"
          class="flex-1 resize-none rounded-xl border border-transparent bg-transparent px-2 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-transparent focus:outline-none disabled:opacity-50 dark:text-slate-100 dark:placeholder:text-slate-500"
          @keydown.enter.exact.prevent="send"
          @input="handleTypingInput"
        />
        <button
          :disabled="sending || !body.trim()"
          @click="send"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition-colors hover:bg-indigo-700 disabled:opacity-40"
        >
          <svg v-if="!sending" class="h-5 w-5 rotate-90" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
            />
          </svg>
          <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import http from '@/services/core/http'
import { getEcho } from '@/services/core/echo'
import { useAuthStore } from '@/stores/auth'
import MessageService from '@/services/messages/MessageService'

interface ChatMessage {
  id: number | string
  sender?: { id?: number; name?: string; email?: string }
  body: string
  created_at: string
  read_at?: string | null
  optimistic?: boolean
}

interface MessageReadPayload {
  conversation_id?: number
  reader_user_id?: number
  last_read_message_id?: number
  read_at?: string
}

export default defineComponent({
  name: 'ChatComponent',
  emits: ['message-sent-local'],
  props: {
    conversationId: {
      type: Number,
      required: true,
    },
  },
  setup() {
    return {
      auth: useAuthStore(),
    }
  },
  data() {
    return {
      messages: [] as ChatMessage[],
      body: '',
      sending: false,
      typingIds: [] as number[],
      typingNames: [] as string[],
      typingTimeoutId: null as number | null,
      typingStateSent: false,
      readAckInFlight: false,
      readAckLastSentMessageId: 0,
      readAckPendingMessageId: 0,
    }
  },
  computed: {
    currentUserId(): number {
      return Number(this.auth.user?.id ?? 0)
    },
    typingText(): string {
      if (!this.typingNames.length) return ''
      if (this.typingNames.length === 1) return `${this.typingNames[0]} is typing...`
      return `${this.typingNames.slice(0, 2).join(', ')} are typing...`
    },
  },
  watch: {
    async conversationId() {
      this.unsubscribe()
      this.typingIds = []
      this.typingNames = []
      this.typingStateSent = false
      await this.loadHistory()
      this.subscribe()
    },
  },
  async mounted() {
    await this.loadHistory()
    this.subscribe()
  },
  beforeUnmount() {
    if (this.typingTimeoutId !== null) {
      window.clearTimeout(this.typingTimeoutId)
      this.typingTimeoutId = null
    }

    this.updateTypingState(false)
    this.unsubscribe()
  },
  methods: {
    async scrollToBottom() {
      await this.$nextTick()
      const thread = this.$refs.threadRef as HTMLElement | undefined
      if (thread) {
        thread.scrollTop = thread.scrollHeight
      }
    },
    formatTime(date: string): string {
      return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    async loadHistory() {
      const response = await http.get(`/conversations/${this.conversationId}/messages`)
      this.messages = response.data?.data ?? []
      const latestMessageId = this.getLatestMessageId()
      if (latestMessageId > 0) {
        this.markConversationRead(latestMessageId)
      }
      await this.scrollToBottom()
    },
    subscribe() {
      if (!this.auth.token) return

      const echo = getEcho(this.auth.token)
      echo
        .private(`conversations.${this.conversationId}`)
        .listen('.message.sent', async (payload: { message?: ChatMessage }) => {
          const incoming = payload?.message
          if (!incoming) return

          const exists = this.messages.some((m) => Number(m.id) === Number(incoming.id))
          if (!exists) {
            this.messages.push(incoming)
            if (Number(incoming.sender?.id ?? 0) !== this.currentUserId) {
              this.markConversationRead(Number(incoming.id))
            }
            await this.scrollToBottom()
          }
        })
        .listen(
          '.user.typing',
          (payload: { user?: { id?: number; name?: string }; is_typing?: boolean }) => {
            const senderId = Number(payload?.user?.id ?? 0)
            const senderName = String(payload?.user?.name ?? '')
            const isTyping = Boolean(payload?.is_typing)

            if (!senderId || senderId === this.currentUserId) return

            if (isTyping) {
              if (!this.typingIds.includes(senderId)) {
                this.typingIds.push(senderId)
              }
              if (senderName && !this.typingNames.includes(senderName)) {
                this.typingNames.push(senderName)
              }
            } else {
              this.typingIds = this.typingIds.filter((id) => id !== senderId)
              this.typingNames = this.typingNames.filter((name) => name !== senderName)
            }
          },
        )
        .listen('.message.read', (payload: MessageReadPayload) => {
          this.applyReadReceipt(payload)
        })
    },
    unsubscribe() {
      if (!this.auth.token) return
      const echo = getEcho(this.auth.token)
      echo.leave(`private-conversations.${this.conversationId}`)
    },
    async sendTyping(isTyping: boolean) {
      await http.post(`/conversations/${this.conversationId}/typing`, {
        is_typing: isTyping,
      })
    },
    getLatestMessageId(): number {
      if (!this.messages.length) return 0
      const latest = this.messages[this.messages.length - 1]
      return Number(latest?.id ?? 0)
    },
    markConversationRead(upToMessageId: number) {
      if (!Number.isFinite(upToMessageId) || upToMessageId <= 0) return

      if (upToMessageId > this.readAckPendingMessageId) {
        this.readAckPendingMessageId = upToMessageId
      }

      void this.flushReadAck()
    },
    async flushReadAck() {
      if (this.readAckInFlight) return

      const target = this.readAckPendingMessageId
      if (target <= this.readAckLastSentMessageId) return

      this.readAckInFlight = true
      try {
        await MessageService.markRead(this.conversationId, target)
        this.readAckLastSentMessageId = Math.max(this.readAckLastSentMessageId, target)
      } catch {
        // Keep pending marker for next retry trigger.
      } finally {
        this.readAckInFlight = false
        if (this.readAckPendingMessageId > this.readAckLastSentMessageId) {
          void this.flushReadAck()
        }
      }
    },
    updateTypingState(nextState: boolean) {
      if (this.typingStateSent === nextState) return

      this.typingStateSent = nextState
      void this.sendTyping(nextState)
    },
    handleTypingInput(e: Event) {
      const el = e.target as HTMLTextAreaElement
      el.style.height = 'auto'
      el.style.height = `${Math.min(el.scrollHeight, 120)}px`

      const hasContent = !!this.body.trim()

      if (hasContent) {
        this.updateTypingState(true)

        if (this.typingTimeoutId !== null) {
          window.clearTimeout(this.typingTimeoutId)
          this.typingTimeoutId = null
        }

        this.typingTimeoutId = window.setTimeout(() => {
          this.updateTypingState(false)
          this.typingTimeoutId = null
        }, 1800)
      } else {
        this.updateTypingState(false)
      }
    },
    async send() {
      const trimmed = this.body.trim()
      if (!trimmed || this.sending) return

      this.sending = true

      const optimisticId = `tmp-${Date.now()}`
      const optimisticMessage: ChatMessage = {
        id: optimisticId,
        body: trimmed,
        sender: {
          id: this.currentUserId,
          name: this.auth.user?.name,
          email: this.auth.user?.email,
        },
        created_at: new Date().toISOString(),
        read_at: null,
        optimistic: true,
      }

      this.messages.push(optimisticMessage)
      this.body = ''
      await this.scrollToBottom()

      const textarea = this.$refs.textareaRef as HTMLTextAreaElement | undefined
      if (textarea) {
        textarea.style.height = 'auto'
      }

      if (this.typingTimeoutId !== null) {
        window.clearTimeout(this.typingTimeoutId)
        this.typingTimeoutId = null
      }

      this.updateTypingState(false)

      try {
        const response = await http.post(`/conversations/${this.conversationId}/messages`, {
          body: trimmed,
        })

        const persisted = response.data?.data as ChatMessage
        persisted.read_at = persisted.read_at ?? null
        this.messages = this.messages.map((m) => (m.id === optimisticId ? persisted : m))
        this.$emit('message-sent-local', persisted)
      } catch {
        this.messages = this.messages.filter((m) => m.id !== optimisticId)
      } finally {
        this.sending = false
      }
    },
    applyReadReceipt(payload: MessageReadPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const lastReadMessageId = Number(payload?.last_read_message_id ?? 0)
      const readerUserId = Number(payload?.reader_user_id ?? 0)
      const readAt = String(payload?.read_at ?? '')

      if (!conversationId || conversationId !== this.conversationId) return
      if (!lastReadMessageId || !readAt) return
      if (!readerUserId || readerUserId === this.currentUserId) return

      this.messages = this.messages.map((message) => {
        const messageId = Number(message.id)
        const senderId = Number(message.sender?.id ?? 0)
        const isOutgoing = senderId === this.currentUserId

        if (!isOutgoing || !Number.isFinite(messageId) || messageId > lastReadMessageId) {
          return message
        }

        return {
          ...message,
          read_at: readAt,
        }
      })
    },
  },
})
</script>

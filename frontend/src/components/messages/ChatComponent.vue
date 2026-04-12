<template>
  <div class="flex h-full min-h-0 flex-col bg-[#e8e3f2] dark:bg-slate-900">
    <div ref="threadRef" class="message-scrollbar min-h-0 flex-1 overflow-y-auto bg-white px-4 py-4 dark:bg-slate-950">
      <div
        v-for="message in messages"
        :key="String(message.id)"
        :class="[
          'mb-3 flex',
          Number(message.sender?.id) === currentUserId ? 'justify-end' : 'justify-start',
        ]"
      >
        <div class="max-w-[85%] md:max-w-lg">
          <div
            :class="[
              'mb-1 flex items-center gap-2',
              Number(message.sender?.id) === currentUserId ? 'justify-end' : 'justify-start',
            ]"
          >
            <div
              :class="[
                'h-6 w-6 shrink-0 overflow-hidden rounded-full bg-[#d7d1ec] text-[10px] font-semibold text-[#4d466b] dark:bg-slate-800 dark:text-slate-300',
                Number(message.sender?.id) === currentUserId ? 'order-2' : '',
              ]"
            >
              <img
                v-if="messageAvatarUrl(message)"
                :src="messageAvatarUrl(message)"
                alt="Author avatar"
                class="h-full w-full object-cover"
              />
              <div v-else class="flex h-full w-full items-center justify-center">
                {{ messageInitials(message) }}
              </div>
            </div>
            <p
              :class="[
                'truncate text-[11px] font-semibold text-[#5b5676] dark:text-slate-300',
                Number(message.sender?.id) === currentUserId ? 'order-1' : '',
              ]"
            >
              {{ messageAuthorName(message) }}
            </p>
          </div>

          <div
            :class="[
              'rounded-2xl px-4 py-2.5 text-sm',
              Number(message.sender?.id) === currentUserId
                ? 'rounded-br-sm bg-linear-to-r from-[#4526c9] to-[#5b45f0] text-white'
                : 'rounded-bl-sm bg-[#f1edf8] text-[#2f2952] dark:bg-slate-900 dark:text-slate-100',
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
    </div>

    <div v-if="typingNames.length" class="px-4 pb-2 text-xs text-[#66628b] dark:text-slate-400">
      {{ typingText }}
    </div>

    <div class="sticky bottom-0 z-10 bg-white px-3 pb-3 pt-2 dark:bg-slate-900">
      <div class="flex items-center gap-2 rounded-full bg-[#e8e3f2] p-2 dark:bg-slate-800">
        <textarea
          ref="textareaRef"
          v-model="body"
          rows="1"
          :disabled="sending"
          placeholder="Type a message"
          class="flex-1 resize-none rounded-full bg-transparent px-2 py-2 text-sm leading-5 text-slate-800 placeholder:text-slate-500 focus:outline-none disabled:opacity-50 dark:text-slate-100 dark:placeholder:text-slate-500"
          @keydown.enter.exact.prevent="send"
          @input="handleTypingInput"
        />
        <button
          :disabled="sending || !body.trim()"
          @click="send"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3f34a6] text-white transition hover:brightness-105 disabled:opacity-40"
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
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import MessageService, { type RealtimeReadPayload } from '@/services/messages/MessageService'
import { resolveAssetUrl } from '@/services/core/url'

interface ChatMessage {
  id: number | string
  sender?: { id?: number; name?: string; email?: string; avatar_url?: string | null }
  body: string
  created_at: string
  read_at?: string | null
  optimistic?: boolean
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
      messageStore: useMessageStore(),
    }
  },
  data() {
    return {
      body: '',
      sending: false,
      typingIds: [] as number[],
      typingNames: [] as string[],
      realtimeTeardown: null as (() => void) | null,
      typingTimeoutId: null as number | null,
      typingStateSent: false,
      readAckInFlight: false,
      readAckLastSentMessageId: 0,
      readAckPendingMessageId: 0,
    }
  },
  computed: {
    messages(): ChatMessage[] {
      return this.messageStore.messages as ChatMessage[]
    },
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
    async conversationId(nextConversationId: number, previousConversationId: number) {
      const oldConversationId = Number(previousConversationId ?? 0)

      if (oldConversationId > 0 && this.typingStateSent) {
        void this.sendTyping(false, oldConversationId)
      }

      this.unsubscribe()
      this.typingIds = []
      this.typingNames = []
      this.typingStateSent = false
      await this.syncConversationViewState()
      this.subscribe(Number(nextConversationId ?? this.conversationId))
    },
  },
  async mounted() {
    await this.syncConversationViewState()
    this.subscribe(this.conversationId)
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
    messageAuthorName(message: ChatMessage): string {
      return String(message.sender?.name ?? '').trim() || 'Unknown user'
    },
    messageAvatarUrl(message: ChatMessage): string {
      const senderAvatar = resolveAssetUrl(message.sender?.avatar_url)
      if (senderAvatar) {
        return senderAvatar
      }

      const senderId = Number(message.sender?.id ?? 0)
      if (senderId === this.currentUserId) {
        return resolveAssetUrl((this.auth.user as { avatar_url?: string | null } | undefined)?.avatar_url)
      }

      return ''
    },
    messageInitials(message: ChatMessage): string {
      const source = this.messageAuthorName(message)
      return source
        .split(' ')
        .map((part) => part.trim())
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || 'U'
    },
    async syncConversationViewState() {
      const latestMessageId = this.getLatestMessageId()
      if (latestMessageId > 0) {
        this.markConversationRead(latestMessageId)
      }
      await this.scrollToBottom()
    },
    subscribe(conversationId?: number) {
      const targetConversationId = Number(conversationId ?? this.conversationId)
      if (!Number.isFinite(targetConversationId) || targetConversationId <= 0) return

      this.unsubscribe()

      this.realtimeTeardown = MessageService.subscribeToConversationRealtime(targetConversationId, {
        onMessageSent: async (payload) => {
          const payloadConversationId = Number(payload?.conversation_id ?? 0)
          if (!payloadConversationId || payloadConversationId !== targetConversationId || payloadConversationId !== this.conversationId) {
            return
          }

          const incoming = payload?.message
          const incomingId = Number(incoming?.id ?? 0)
          if (!incoming || !Number.isFinite(incomingId) || incomingId <= 0) return

          const normalizedIncoming: ChatMessage = {
            id: incomingId,
            body: String(incoming.body ?? ''),
            created_at: String(incoming.created_at ?? new Date().toISOString()),
            read_at: incoming.read_at ?? null,
            sender: {
              id: Number(incoming.sender?.id ?? 0) || undefined,
              name: String(incoming.sender?.name ?? ''),
              email: String(incoming.sender?.email ?? ''),
              avatar_url: (incoming.sender as { avatar_url?: string | null } | undefined)?.avatar_url ?? null,
            },
          }

          const exists = this.messages.some((m) => Number(m.id) === incomingId)
          if (!exists) {
            this.messages.push(normalizedIncoming)
            if (Number(normalizedIncoming.sender?.id ?? 0) !== this.currentUserId) {
              this.markConversationRead(incomingId)
            }
            await this.scrollToBottom()
          }
        },
        onUserTyping: (payload) => {
          const payloadConversationId = Number(payload?.conversation_id ?? 0)
          if (!payloadConversationId || payloadConversationId !== targetConversationId || payloadConversationId !== this.conversationId) {
            return
          }

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
        onMessageRead: (payload) => {
          const payloadConversationId = Number(payload?.conversation_id ?? 0)
          if (!payloadConversationId || payloadConversationId !== targetConversationId || payloadConversationId !== this.conversationId) {
            return
          }

          this.applyReadReceipt(payload)
        },
      })
    },
    unsubscribe() {
      if (this.realtimeTeardown) {
        this.realtimeTeardown()
        this.realtimeTeardown = null
      }
    },
    async sendTyping(isTyping: boolean, conversationId?: number) {
      const targetConversationId = Number(conversationId ?? this.conversationId)
      if (!Number.isFinite(targetConversationId) || targetConversationId <= 0) return

      await MessageService.setTyping(targetConversationId, isTyping)
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
      this.body = ''

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
        const persisted = await this.messageStore.sendMessage(trimmed)
        if (persisted) {
          this.$emit('message-sent-local', persisted as ChatMessage)
          await this.scrollToBottom()
        }
      } catch {
        this.body = trimmed
      } finally {
        this.sending = false
      }
    },
    applyReadReceipt(payload: RealtimeReadPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const lastReadMessageId = Number(payload?.last_read_message_id ?? 0)
      const readerUserId = Number(payload?.reader_user_id ?? 0)
      const readAt = String(payload?.read_at ?? '')

      if (!conversationId || conversationId !== this.conversationId) return
      if (!lastReadMessageId || !readAt) return
      if (!readerUserId || readerUserId === this.currentUserId) return

      for (const message of this.messages) {
        const messageId = Number(message.id)
        const senderId = Number(message.sender?.id ?? 0)
        const isOutgoing = senderId === this.currentUserId

        if (!isOutgoing || !Number.isFinite(messageId) || messageId > lastReadMessageId) {
          continue
        }

        message.read_at = readAt
      }
    },
  },
})
</script>

<style scoped>
.message-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.message-scrollbar::-webkit-scrollbar {
  width: 10px;
}

.message-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.message-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 9999px;
  border: 2px solid transparent;
  background-clip: content-box;
}

.message-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}

:global(.dark) .message-scrollbar {
  scrollbar-color: #475569 transparent;
}

:global(.dark) .message-scrollbar::-webkit-scrollbar-thumb {
  background-color: #475569;
}

:global(.dark) .message-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #64748b;
}
</style>

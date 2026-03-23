<template>
  <AppLayout>
    <div class="-m-6 h-[calc(100vh-4rem)] bg-slate-100 md:p-5">
      <div class="h-full overflow-hidden border border-slate-200 bg-white shadow-sm md:rounded-2xl">
        <div class="flex h-full overflow-hidden">
          <div
            :class="[
              'w-full md:w-90 shrink-0 border-r border-slate-200 flex flex-col bg-white',
              currentConversation ? 'hidden md:flex' : 'flex',
            ]"
          >
            <div class="border-b border-slate-200 bg-white px-4 py-3">
              <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Messages</h2>
                <button
                  @click="showNewConversation = !showNewConversation"
                  class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                >
                  New chat
                </button>
              </div>
              <p class="mt-0.5 text-xs text-slate-500">{{ messageStore.totalUnread }} unread</p>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search chats"
                class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none"
              />
            </div>

            <NewConversationForm
              :visible="showNewConversation"
              :recipient-query="newParticipantQuery"
              :selected-recipient="selectedRecipient"
              :recipient-options="recipientOptions"
              :searching-users="searchingUsers"
              :error-message="newConvError"
              :creating="creating"
              @update:recipient-query="onRecipientQueryChange"
              @select-recipient="selectRecipient"
              @clear-recipient="clearSelectedRecipient"
              @submit="startConversation"
            />

            <ConversationList
              :conversations="filteredConversations"
              :active-id="currentConversation?.id"
              :loading="messageStore.loading"
              :current-user-id="Number(auth.user?.id ?? 0)"
              @select="openConversation"
              class="flex-1 overflow-y-auto"
            />
          </div>

          <div
            :class="[
              'flex-1 flex flex-col bg-slate-50',
              !currentConversation ? 'hidden md:flex' : 'flex',
            ]"
          >
            <div
              v-if="!currentConversation"
              class="flex-1 flex items-center justify-center text-slate-400"
            >
              <div class="px-6 text-center">
                <p class="text-2xl font-semibold text-slate-700">Your chats</p>
                <p class="mt-2 text-sm">Select a conversation from the left and start messaging.</p>
              </div>
            </div>

            <template v-else>
              <ConversationHeader :participant-names="participantNames" @back="backToList" />
              <ChatComponent
                :conversation-id="currentConversation.id"
                @message-sent-local="onLocalMessageSent"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { getEcho } from '@/services/core/echo'
import MessageService from '@/services/messages/MessageService'
import {
  buildParticipantNames,
  toNewConversationPayload,
  validateNewConversation,
} from '@/services/messages/MessagesViewService'
import AppLayout from '@/layouts/AppLayout.vue'
import ConversationList from '@/components/messages/ConversationList.vue'
import NewConversationForm from '@/components/messages/NewConversationForm.vue'
import ConversationHeader from '@/components/messages/ConversationHeader.vue'
import ChatComponent from '@/components/messages/ChatComponent.vue'

interface MessageConversation {
  id: number
  subject?: string
  participants?: Array<{ id?: number; name?: string }>
  unread_count?: number
  last_message?: {
    id?: number
    body?: string
    created_at?: string
    read_at?: string | null
    sender?: {
      id?: number
      name?: string
      email?: string
    }
  }
  [key: string]: unknown
}

interface MessageSentPayload {
  conversation_id?: number
  message?: MessageConversation['last_message']
}

interface MessageReadPayload {
  conversation_id?: number
  reader_user_id?: number
  last_read_message_id?: number
  read_at?: string
}

interface RecipientOption {
  id: number
  name: string
  email: string
}

export default defineComponent({
  name: 'MessagesView',
  components: {
    AppLayout,
    ConversationList,
    NewConversationForm,
    ConversationHeader,
    ChatComponent,
  },
  setup() {
    return {
      auth: useAuthStore(),
      messageStore: useMessageStore(),
    }
  },
  data() {
    return {
      showNewConversation: false,
      newParticipantQuery: '',
      selectedRecipient: null as RecipientOption | null,
      recipientOptions: [] as RecipientOption[],
      searchingUsers: false,
      newConvError: '',
      creating: false,
      searchQuery: '',
      subscribedConversationIds: [] as number[],
      recipientSearchTimerId: null as ReturnType<typeof setTimeout> | null,
      latestRecipientSearchNonce: 0,
    }
  },
  computed: {
    currentConversation(): MessageConversation | null {
      return this.messageStore.currentConversation as MessageConversation | null
    },
    participantNames(): string {
      return buildParticipantNames(this.currentConversation, this.auth.user?.id)
    },
    filteredConversations(): MessageConversation[] {
      const q = this.searchQuery.trim().toLowerCase()
      if (!q) return this.messageStore.conversations as MessageConversation[]

      return (this.messageStore.conversations as MessageConversation[]).filter((conversation) => {
        const names = String(buildParticipantNames(conversation, this.auth.user?.id)).toLowerCase()
        const lastMessage = String(
          (conversation as { last_message?: { body?: string } }).last_message?.body ?? '',
        ).toLowerCase()

        return names.includes(q) || lastMessage.includes(q)
      })
    },
    conversationIds(): number[] {
      return (this.messageStore.conversations as MessageConversation[])
        .map((conversation) => Number(conversation.id))
        .filter((id) => Number.isFinite(id) && id > 0)
    },
    conversationIdsKey(): string {
      return this.conversationIds.join(',')
    },
  },
  watch: {
    conversationIdsKey() {
      this.refreshRealtimeSubscriptions()
    },
  },
  async mounted() {
    await this.messageStore.fetchConversations()
    this.refreshRealtimeSubscriptions()
  },
  beforeUnmount() {
    if (this.recipientSearchTimerId) {
      clearTimeout(this.recipientSearchTimerId)
      this.recipientSearchTimerId = null
    }

    this.unsubscribeAllRealtime()
  },
  methods: {
    onRecipientQueryChange(value: string) {
      this.newParticipantQuery = value
      this.selectedRecipient = null
      this.newConvError = ''

      if (this.recipientSearchTimerId) {
        clearTimeout(this.recipientSearchTimerId)
      }

      this.recipientSearchTimerId = setTimeout(() => {
        this.searchRecipients(this.newParticipantQuery)
      }, 220)
    },
    selectRecipient(option: RecipientOption) {
      this.selectedRecipient = option
      this.newParticipantQuery = `${option.name} (${option.email})`
      this.recipientOptions = []
      this.newConvError = ''
    },
    clearSelectedRecipient() {
      this.selectedRecipient = null
      this.newParticipantQuery = ''
      this.recipientOptions = []
    },
    async searchRecipients(rawQuery: string) {
      const query = rawQuery.trim()
      if (query.length < 2) {
        this.recipientOptions = []
        this.searchingUsers = false
        return
      }

      const nonce = ++this.latestRecipientSearchNonce
      this.searchingUsers = true

      try {
        const response = await MessageService.searchConversationUsers(query)
        if (nonce !== this.latestRecipientSearchNonce) {
          return
        }

        const options = Array.isArray(response?.data) ? response.data : []
        this.recipientOptions = options
          .map((item: Partial<RecipientOption>) => ({
            id: Number(item.id ?? 0),
            name: String(item.name ?? ''),
            email: String(item.email ?? ''),
          }))
          .filter((item: RecipientOption) => item.id > 0 && item.name && item.email)
      } catch {
        if (nonce === this.latestRecipientSearchNonce) {
          this.recipientOptions = []
        }
      } finally {
        if (nonce === this.latestRecipientSearchNonce) {
          this.searchingUsers = false
        }
      }
    },
    refreshRealtimeSubscriptions() {
      if (!this.auth.token) return

      const echo = getEcho(this.auth.token)
      const wanted = new Set(this.conversationIds)

      for (const conversationId of this.subscribedConversationIds) {
        if (!wanted.has(conversationId)) {
          echo.leave(`private-conversations.${conversationId}`)
        }
      }

      const current = new Set(this.subscribedConversationIds)
      for (const conversationId of wanted) {
        if (current.has(conversationId)) continue

        echo
          .private(`conversations.${conversationId}`)
          .listen('.message.sent', (payload: MessageSentPayload) => {
            this.onRealtimeMessage(payload)
          })
          .listen('.message.read', (payload: MessageReadPayload) => {
            this.onRealtimeRead(payload)
          })
      }

      this.subscribedConversationIds = [...wanted]
    },
    unsubscribeAllRealtime() {
      if (!this.auth.token) return

      const echo = getEcho(this.auth.token)
      for (const conversationId of this.subscribedConversationIds) {
        echo.leave(`private-conversations.${conversationId}`)
      }

      this.subscribedConversationIds = []
    },
    onRealtimeMessage(payload: MessageSentPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const incomingMessage = payload?.message
      if (!conversationId || !incomingMessage) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const index = conversations.findIndex(
        (conversation) => Number(conversation.id) === conversationId,
      )
      if (index === -1) return

      const conversation = conversations[index]
      if (!conversation) return

      conversation.last_message = incomingMessage

      const senderId = Number(incomingMessage.sender?.id ?? 0)
      const isCurrentUserSender = senderId === Number(this.auth.user?.id ?? 0)
      const isOpenConversation = Number(this.currentConversation?.id ?? 0) === conversationId

      if (!isCurrentUserSender && !isOpenConversation) {
        conversation.unread_count = Number(conversation.unread_count ?? 0) + 1
      } else {
        conversation.unread_count = 0
      }

      if (index > 0) {
        const moved = conversations.splice(index, 1)[0]
        if (moved) {
          conversations.unshift(moved)
        }
      }
    },
    onRealtimeRead(payload: MessageReadPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const readerUserId = Number(payload?.reader_user_id ?? 0)
      const lastReadMessageId = Number(payload?.last_read_message_id ?? 0)
      const readAt = String(payload?.read_at ?? '')

      if (!conversationId || !readerUserId || !lastReadMessageId || !readAt) return
      if (readerUserId === Number(this.auth.user?.id ?? 0)) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const conversation = conversations.find((item) => Number(item.id) === conversationId)
      if (!conversation?.last_message) return

      const lastMessageId = Number(conversation.last_message.id ?? 0)
      const lastMessageSenderId = Number(conversation.last_message.sender?.id ?? 0)
      const isCurrentUserSender = lastMessageSenderId === Number(this.auth.user?.id ?? 0)

      if (!isCurrentUserSender || !lastMessageId || lastMessageId > lastReadMessageId) {
        return
      }

      conversation.last_message.read_at = readAt
    },
    onLocalMessageSent(message: MessageConversation['last_message']) {
      const conversationId = Number(this.currentConversation?.id ?? 0)
      if (!conversationId || !message) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const index = conversations.findIndex((item) => Number(item.id) === conversationId)
      if (index === -1) return

      const conversation = conversations[index]
      if (!conversation) return

      conversation.last_message = {
        ...message,
        read_at: message.read_at ?? null,
      }
      conversation.unread_count = 0

      if (index > 0) {
        const moved = conversations.splice(index, 1)[0]
        if (moved) {
          conversations.unshift(moved)
        }
      }
    },
    async openConversation(id: number) {
      await this.messageStore.openConversation(id)
    },
    backToList() {
      this.messageStore.currentConversation = null
    },
    async startConversation() {
      const validationError = validateNewConversation(this.selectedRecipient?.id ?? null)
      if (validationError) {
        this.newConvError = validationError
        return
      }

      this.newConvError = ''
      this.creating = true

      try {
        const payload = toNewConversationPayload(this.selectedRecipient?.id ?? 0)
        await this.messageStore.startConversation(payload)
        this.refreshRealtimeSubscriptions()
        this.showNewConversation = false
        this.newParticipantQuery = ''
        this.selectedRecipient = null
        this.recipientOptions = []
      } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } }
        this.newConvError = err?.response?.data?.message ?? 'Failed to start conversation.'
      } finally {
        this.creating = false
      }
    },
  },
})
</script>

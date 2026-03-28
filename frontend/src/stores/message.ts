import { defineStore } from 'pinia'
import MessageService from '@/services/messages/MessageService'

interface Message {
  id: number
  sender: Record<string, unknown>
  body: string
  read_at: string | null
  created_at: string
}

interface Conversation {
  id: number
  project: Record<string, unknown> | null
  participants: Record<string, unknown>[]
  last_message: Message | null
  unread_count: number
  created_at: string
}

interface MessageState {
  conversations: Conversation[]
  currentConversation: Conversation | null
  messages: Message[]
  messagesPagination: Record<string, unknown> | null
  loading: boolean
  sending: boolean
}

export const useMessageStore = defineStore('message', {
  state: (): MessageState => ({
    conversations: [],
    currentConversation: null,
    messages: [],
    messagesPagination: null,
    loading: false,
    sending: false,
  }),

  getters: {
    totalUnread: (state): number =>
      state.conversations.reduce((sum, c) => sum + (c.unread_count ?? 0), 0),
  },

  actions: {
    upsertConversation(conversation: Conversation) {
      const index = this.conversations.findIndex((item) => item.id === conversation.id)

      if (index === -1) {
        this.conversations.push(conversation)
        return
      }

      this.conversations[index] = {
        ...this.conversations[index],
        ...conversation,
      }
    },

    async fetchConversations() {
      this.loading = true
      try {
        const result = await MessageService.getConversations()
        this.conversations = result.data
      } finally {
        this.loading = false
      }
    },

    async openConversation(id: number) {
      const [convResult, msgResult] = await Promise.all([
        MessageService.getConversation(id),
        MessageService.getMessages(id),
      ])

      const openedConversation = {
        ...(convResult.data as Conversation),
        unread_count: 0,
      }

      this.currentConversation = openedConversation
      this.messages = msgResult.data
      this.messagesPagination = msgResult.meta ?? null

      // Keep sidebar synchronized with server payload without flashing loading state.
      this.upsertConversation(openedConversation)
    },

    async sendMessage(body: string) {
      if (!this.currentConversation) return
      this.sending = true
      try {
        const result = await MessageService.sendMessage(this.currentConversation.id, body)
        this.messages.push(result.data)

        const index = this.conversations.findIndex((c) => c.id === this.currentConversation?.id)
        if (index !== -1) {
          const conversation = this.conversations[index]
          if (conversation) {
            conversation.last_message = result.data
            conversation.unread_count = 0
          }
        }

        return result.data as Message
      } finally {
        this.sending = false
      }
    },

    async setTyping(isTyping: boolean) {
      if (!this.currentConversation) return
      await MessageService.setTyping(this.currentConversation.id, isTyping)
    },

    async startConversation(payload: Record<string, unknown>) {
      const result = await MessageService.createConversation(payload)

      this.upsertConversation(result.data as Conversation)
      await this.openConversation((result.data as Conversation).id)
      return result.data as Conversation
    },
  },
})

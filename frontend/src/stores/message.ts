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
      this.loading = true
      try {
        const [convResult, msgResult] = await Promise.all([
          MessageService.getConversation(id),
          MessageService.getMessages(id),
        ])
        this.currentConversation = convResult.data
        this.messages = msgResult.data
        this.messagesPagination = msgResult.meta ?? null

        // Mark as read in conversations list
        const index = this.conversations.findIndex((c) => c.id === id)
        if (index !== -1) {
          const conversation = this.conversations[index]
          if (conversation) conversation.unread_count = 0
        }
      } finally {
        this.loading = false
      }
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
      const existing = this.conversations.find((c) => c.id === result.data.id)
      if (!existing) this.conversations.unshift(result.data)
      await this.openConversation(result.data.id)
      return result.data as Conversation
    },
  },
})

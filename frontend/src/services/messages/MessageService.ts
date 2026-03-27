import http from '@/services/core/http'
import { getEcho } from '@/services/core/echo'

export interface RealtimeMessagePayload {
  conversation_id?: number
  message?: {
    id?: number | string
    body?: string
    created_at?: string
    read_at?: string | null
    sender?: {
      id?: number
      name?: string
      email?: string
    }
  }
}

export interface RealtimeReadPayload {
  conversation_id?: number
  reader_user_id?: number
  last_read_message_id?: number
  read_at?: string
}

export interface RealtimeTypingPayload {
  user?: {
    id?: number
    name?: string
  }
  is_typing?: boolean
}

interface ConversationRealtimeHandlers {
  onMessageSent?: (payload: RealtimeMessagePayload) => void | Promise<void>
  onMessageRead?: (payload: RealtimeReadPayload) => void | Promise<void>
  onUserTyping?: (payload: RealtimeTypingPayload) => void | Promise<void>
}

const MessageService = {
  async getConversations() {
    const { data } = await http.get('/conversations')
    return data
  },

  async createConversation(payload: Record<string, unknown>) {
    const { data } = await http.post('/conversations', payload)
    return data
  },

  async searchConversationUsers(query: string, limit = 8, projectId?: number | null) {
    const params: Record<string, unknown> = {
      q: query,
      limit,
    }

    const normalizedProjectId = Number(projectId ?? 0)
    if (Number.isFinite(normalizedProjectId) && normalizedProjectId > 0) {
      params.project_id = normalizedProjectId
    }

    const { data } = await http.get('/conversation-users', {
      params,
    })
    return data
  },

  async createGroupConversation(payload: {
    project_id: number
    subject: string
    participant_user_ids: number[]
  }) {
    const { data } = await http.post('/conversations', {
      ...payload,
      type: 'group',
    })
    return data
  },

  async addConversationParticipant(conversationId: number, userId: number) {
    const { data } = await http.post(`/conversations/${conversationId}/participants`, {
      user_id: userId,
    })
    return data
  },

  async removeConversationParticipant(conversationId: number, userId: number) {
    const { data } = await http.delete(`/conversations/${conversationId}/participants/${userId}`)
    return data
  },

  async getConversation(id: number) {
    const { data } = await http.get(`/conversations/${id}`)
    return data
  },

  async getMessages(conversationId: number, params: Record<string, unknown> = {}) {
    const { data } = await http.get(`/conversations/${conversationId}/messages`, { params })
    return data
  },

  async sendMessage(conversationId: number, body: string) {
    const { data } = await http.post(`/conversations/${conversationId}/messages`, { body })
    return data
  },

  async markRead(conversationId: number, upToMessageId?: number) {
    const payload: Record<string, unknown> = {}
    if (typeof upToMessageId === 'number' && Number.isFinite(upToMessageId) && upToMessageId > 0) {
      payload.up_to_message_id = upToMessageId
    }

    const { data } = await http.post(`/conversations/${conversationId}/read`, payload)
    return data
  },

  async setTyping(conversationId: number, isTyping: boolean) {
    const { data } = await http.post(`/conversations/${conversationId}/typing`, {
      is_typing: isTyping,
    })
    return data
  },

  subscribeToConversationRealtime(
    token: string,
    conversationId: number,
    handlers: ConversationRealtimeHandlers,
  ) {
    if (!token) return

    const echo = getEcho(token)
    const channel = echo.private(`conversations.${conversationId}`)

    if (handlers.onMessageSent) {
      channel.listen('.message.sent', handlers.onMessageSent)
    }

    if (handlers.onMessageRead) {
      channel.listen('.message.read', handlers.onMessageRead)
    }

    if (handlers.onUserTyping) {
      channel.listen('.user.typing', handlers.onUserTyping)
    }
  },

  unsubscribeFromConversationRealtime(token: string, conversationId: number) {
    if (!token) return
    const echo = getEcho(token)
    echo.leave(`private-conversations.${conversationId}`)
  },
}

export default MessageService

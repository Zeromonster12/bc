import http from '@/services/core/http'

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
}

export default MessageService

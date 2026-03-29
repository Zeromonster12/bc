import type { RealtimeMessagePayload, RealtimeReadPayload } from '@/services/messages/MessageService'

interface Participant {
  id?: number
  name?: string
}

interface Conversation {
  participants?: Participant[]
}

export interface RecipientOption {
  id: number
  name: string
  email: string
}

export interface ProjectOption {
  id: number
  title: string
}

interface ConversationParticipant {
  id?: number
  is_admin?: boolean
}

interface ConversationPreviewMessage {
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

interface ConversationPreview {
  id: number
  participants?: ConversationParticipant[]
  unread_count?: number
  last_message?: ConversationPreviewMessage | null
}

export const buildParticipantNames = (
  conversation: Conversation | null,
  currentUserId?: number,
): string => {
  if (!conversation?.participants) return ''
  return conversation.participants
    .filter((p) => p.id !== currentUserId)
    .map((p) => p.name ?? '')
    .filter(Boolean)
    .join(', ')
}

export const validateNewConversation = (recipientUserId: number | null): string => {
  if (!recipientUserId || recipientUserId <= 0) {
    return 'Recipient is required.'
  }
  return ''
}

export const toNewConversationPayload = (recipientUserId: number): Record<string, number> => ({
  recipient_user_id: recipientUserId,
})

export const validateNewGroupConversation = (
  projectId: number | null,
  subject: string,
  participantUserIds: number[],
): string => {
  const normalizedProjectId = Number(projectId ?? 0)
  if (!Number.isFinite(normalizedProjectId) || normalizedProjectId <= 0) {
    return 'Project is required.'
  }

  const cleanSubject = String(subject ?? '').trim()
  if (cleanSubject.length < 3) {
    return 'Group name must have at least 3 characters.'
  }

  if (!Array.isArray(participantUserIds) || participantUserIds.length < 1) {
    return 'Select at least one participant.'
  }

  return ''
}

export const toNewGroupConversationPayload = (
  projectId: number,
  subject: string,
  participantUserIds: number[],
): {
  project_id: number
  subject: string
  participant_user_ids: number[]
} => ({
  project_id: projectId,
  subject: subject.trim(),
  participant_user_ids: Array.from(
    new Set(
      participantUserIds
        .map((id) => Number(id))
        .filter((id) => Number.isFinite(id) && id > 0),
    ),
  ),
})

export const normalizeRecipientOptions = (source: unknown): RecipientOption[] => {
  const rows = Array.isArray(source) ? source : []

  return rows
    .map((item: unknown) => {
      const row = item as { id?: number; name?: string; email?: string }
      return {
        id: Number(row.id ?? 0),
        name: String(row.name ?? ''),
        email: String(row.email ?? ''),
      }
    })
    .filter((item) => item.id > 0 && item.name.length > 0 && item.email.length > 0)
}

export const normalizeProjectOptions = (source: unknown): ProjectOption[] => {
  const rows = Array.isArray(source) ? source : []

  return rows
    .map((item: unknown) => {
      const row = item as { id?: number; title?: string }
      return {
        id: Number(row.id ?? 0),
        title: String(row.title ?? '').trim(),
      }
    })
    .filter((item) => item.id > 0 && item.title.length > 0)
}

export const buildGroupAddCandidates = (
  source: unknown,
  participants: Array<{ id?: number }> = [],
): RecipientOption[] => {
  const existingIds = new Set(
    participants
      .map((participant) => Number(participant.id ?? 0))
      .filter((id) => Number.isFinite(id) && id > 0),
  )

  return normalizeRecipientOptions(source)
    .filter((item: RecipientOption) => item.id > 0 && !existingIds.has(item.id))
}

export const canDemoteGroupParticipant = (
  participants: Array<{ id?: number; is_admin?: boolean }>,
  participantUserId: number,
): boolean => {
  if (!Number.isFinite(participantUserId) || participantUserId <= 0) {
    return false
  }

  const participant = participants.find(
    (candidate) => Number(candidate.id ?? 0) === participantUserId,
  )

  if (!participant?.is_admin) {
    return false
  }

  const adminCount = participants.filter((candidate) => Boolean(candidate.is_admin)).length
  return adminCount > 1
}

export const applyRealtimeConversationMessagePreview = (
  conversations: ConversationPreview[],
  payload: RealtimeMessagePayload,
  currentUserId: number,
  openConversationId: number,
): void => {
  const conversationId = Number(payload?.conversation_id ?? 0)
  const incomingMessage = payload?.message
  if (!conversationId || !incomingMessage) return

  const incomingMessageId = Number(incomingMessage.id ?? 0)
  if (!Number.isFinite(incomingMessageId) || incomingMessageId <= 0) return

  const normalizedMessage: NonNullable<ConversationPreview['last_message']> = {
    id: incomingMessageId,
    body: String(incomingMessage.body ?? ''),
    created_at: String(incomingMessage.created_at ?? ''),
    read_at: incomingMessage.read_at ?? null,
    sender: {
      id: Number(incomingMessage.sender?.id ?? 0) || undefined,
      name: String(incomingMessage.sender?.name ?? ''),
      email: String(incomingMessage.sender?.email ?? ''),
    },
  }

  const index = conversations.findIndex(
    (conversation) => Number(conversation.id) === conversationId,
  )
  if (index === -1) return

  const conversation = conversations[index]
  if (!conversation) return

  conversation.last_message = normalizedMessage

  const senderId = Number(normalizedMessage.sender?.id ?? 0)
  const isCurrentUserSender = senderId === currentUserId
  const isOpenConversation = openConversationId === conversationId

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
}

export const applyRealtimeConversationReadPreview = (
  conversations: ConversationPreview[],
  payload: RealtimeReadPayload,
  currentUserId: number,
): void => {
  const conversationId = Number(payload?.conversation_id ?? 0)
  const readerUserId = Number(payload?.reader_user_id ?? 0)
  const lastReadMessageId = Number(payload?.last_read_message_id ?? 0)
  const readAt = String(payload?.read_at ?? '')

  if (!conversationId || !readerUserId || !lastReadMessageId || !readAt) return
  if (readerUserId === currentUserId) return

  const conversation = conversations.find((item) => Number(item.id) === conversationId)
  if (!conversation?.last_message) return

  const lastMessageId = Number(conversation.last_message.id ?? 0)
  const lastMessageSenderId = Number(conversation.last_message.sender?.id ?? 0)
  const isCurrentUserSender = lastMessageSenderId === currentUserId

  if (!isCurrentUserSender || !lastMessageId || lastMessageId > lastReadMessageId) {
    return
  }

  conversation.last_message.read_at = readAt
}

export const applyLocalConversationMessagePreview = (
  conversations: ConversationPreview[],
  conversationId: number,
  message: ConversationPreview['last_message'] | undefined,
): void => {
  if (!conversationId || !message) return

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
}

export const extractAcceptedApplicationProjects = (source: unknown): ProjectOption[] => {
  const rows = Array.isArray(source) ? source : []
  const uniqueByProject = new Map<number, ProjectOption>()

  rows.forEach((item: unknown) => {
    const row = item as { project?: { id?: number; title?: string } }
    const projectId = Number(row.project?.id ?? 0)
    const projectTitle = String(row.project?.title ?? '').trim()

    if (projectId > 0 && projectTitle.length > 0) {
      uniqueByProject.set(projectId, { id: projectId, title: projectTitle })
    }
  })

  return Array.from(uniqueByProject.values())
}

export const extractErrorMessage = (error: unknown, fallback: string): string => {
  const err = error as { response?: { data?: { message?: string } } }
  const message = String(err?.response?.data?.message ?? '').trim()
  return message || fallback
}

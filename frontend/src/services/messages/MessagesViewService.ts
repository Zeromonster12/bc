interface Participant {
  id?: number
  name?: string
}

interface Conversation {
  participants?: Participant[]
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

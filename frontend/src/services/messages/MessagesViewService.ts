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

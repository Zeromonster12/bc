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

import http from '@/services/core/http'
import { getEcho } from '@/services/core/echo'

export interface NotificationData {
  [key: string]: unknown
  conversation_id?: number
  message_id?: number
  sender_user_id?: number
  sender_name?: string
  kind?: string
  title?: string
  body?: string
}

export interface AppNotification {
  id: string
  type: string
  title: string
  body: string
  data: NotificationData
  read_at: string | null
  created_at: string
}

export interface NotificationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface NotificationListParams {
  page?: number
  per_page?: number
}

export interface NotificationUiMeta {
  icon: 'Bell' | 'MessageSquare' | 'CheckCircle2' | 'XCircle'
  actionLabel: string
  dotClass: string
  accentTextClass: string
}

export type NotificationAction =
  | { kind: 'conversation'; conversationId: number }
  | { kind: 'company-profile'; approvalStatus: 'approved' | 'rejected' }
  | { kind: 'none' }

const DEFAULT_NOTIFICATION_UI_META: NotificationUiMeta = {
  icon: 'Bell',
  actionLabel: '',
  dotClass: 'bg-indigo-500',
  accentTextClass: 'text-indigo-600 dark:text-indigo-300',
}

const NOTIFICATION_UI_META_MAP: Record<string, NotificationUiMeta> = {
  'message.received': {
    icon: 'MessageSquare',
    actionLabel: 'Open conversation',
    dotClass: 'bg-indigo-500',
    accentTextClass: 'text-indigo-600 dark:text-indigo-300',
  },
  'company.approved': {
    icon: 'CheckCircle2',
    actionLabel: 'Open company profile',
    dotClass: 'bg-emerald-500',
    accentTextClass: 'text-emerald-600 dark:text-emerald-300',
  },
  'company.rejected': {
    icon: 'XCircle',
    actionLabel: 'Review company profile',
    dotClass: 'bg-rose-500',
    accentTextClass: 'text-rose-600 dark:text-rose-300',
  },
}

const USER_NOTIFICATION_EVENT = '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated'
const USER_NOTIFICATION_EVENT_ALT = 'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated'

type NotificationChannel = {
  listen: (event: string, callback: (payload: unknown) => void) => NotificationChannel
  stopListening: (event: string, callback?: (payload: unknown) => void) => NotificationChannel
  notification?: (callback: (payload: unknown) => void) => NotificationChannel
}

const toRecord = (value: unknown): Record<string, unknown> | null => {
  if (typeof value !== 'object' || value === null) {
    return null
  }

  return value as Record<string, unknown>
}

const toPositiveInt = (value: unknown): number | null => {
  const numeric = Number(value)

  if (!Number.isFinite(numeric) || numeric <= 0) {
    return null
  }

  return Math.trunc(numeric)
}

const toIsoStringOrNull = (value: unknown): string | null => {
  if (typeof value !== 'string') {
    return null
  }

  const trimmed = value.trim()

  return trimmed !== '' ? trimmed : null
}

export const resolveNotificationUiMeta = (type: string): NotificationUiMeta => {
  const normalizedType = String(type ?? '').trim()

  if (normalizedType === '') {
    return DEFAULT_NOTIFICATION_UI_META
  }

  return NOTIFICATION_UI_META_MAP[normalizedType] ?? DEFAULT_NOTIFICATION_UI_META
}

export const resolveNotificationAction = (notification: AppNotification): NotificationAction => {
  if (notification.type === 'message.received') {
    const conversationId = toPositiveInt(notification.data.conversation_id)

    if (conversationId) {
      return {
        kind: 'conversation',
        conversationId,
      }
    }
  }

  if (notification.type === 'company.approved' || notification.type === 'company.rejected') {
    const fallbackStatus = notification.type === 'company.approved' ? 'approved' : 'rejected'
    const candidate = String(notification.data.company_verification_status ?? fallbackStatus)
      .trim()
      .toLowerCase()

    return {
      kind: 'company-profile',
      approvalStatus: candidate === 'rejected' ? 'rejected' : 'approved',
    }
  }

  return { kind: 'none' }
}

const normalizeNotificationInternal = (raw: unknown): AppNotification | null => {
  const root = toRecord(raw)

  if (!root) {
    return null
  }

  const nestedData = toRecord(root.data)
  const data = (nestedData ?? root) as NotificationData

  const hasTopLevelNotificationShape =
    root.id !== undefined ||
    root.type !== undefined ||
    root.read_at !== undefined ||
    root.created_at !== undefined

  const hasPayloadNotificationShape =
    data.kind !== undefined ||
    data.title !== undefined ||
    data.body !== undefined ||
    data.conversation_id !== undefined ||
    data.message_id !== undefined ||
    data.company_verification_status !== undefined

  if (!hasTopLevelNotificationShape && !hasPayloadNotificationShape) {
    return null
  }

  const explicitId = root.id
  const nestedId = data.id
  const rawId =
    typeof explicitId === 'string'
      ? explicitId
      : typeof explicitId === 'number'
        ? String(explicitId)
        : typeof nestedId === 'string'
          ? nestedId
          : typeof nestedId === 'number'
            ? String(nestedId)
            : ''

  const id = rawId.trim() !== ''
    ? rawId.trim()
    : `realtime-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`

  const type = String(data.kind ?? root.type ?? 'notification').trim() || 'notification'
  const title = String(data.title ?? 'Notification').trim() || 'Notification'
  const body = String(data.body ?? '').trim()

  const readAt = toIsoStringOrNull(root.read_at) ?? toIsoStringOrNull(data.read_at) ?? null
  const createdAt =
    toIsoStringOrNull(root.created_at) ??
    toIsoStringOrNull(data.created_at) ??
    new Date().toISOString()

  return {
    id,
    type,
    title,
    body,
    data,
    read_at: readAt,
    created_at: createdAt,
  }
}

const realtimePayloadCandidates = (raw: unknown): unknown[] => {
  const root = toRecord(raw)

  if (!root) {
    return [raw]
  }

  const candidates: unknown[] = []

  if (root.notification !== undefined) {
    candidates.push(root.notification)
  }

  const nestedData = toRecord(root.data)

  if (nestedData) {
    candidates.push(nestedData)
  }

  if (nestedData?.notification !== undefined) {
    candidates.push(nestedData.notification)
  }

  candidates.push(root)

  return candidates
}

const scoreRealtimeCandidate = (notification: AppNotification): number => {
  let score = 0

  if (!notification.id.startsWith('realtime-')) {
    score += 4
  }

  if (notification.type !== 'notification') {
    score += 3
  }

  if (
    notification.data.kind !== undefined ||
    notification.data.title !== undefined ||
    notification.data.body !== undefined
  ) {
    score += 2
  }

  if (notification.data.conversation_id !== undefined) {
    score += 1
  }

  return score
}

export const normalizeApiNotification = (raw: unknown): AppNotification | null =>
  normalizeNotificationInternal(raw)

export const normalizeRealtimeNotification = (raw: unknown): AppNotification | null => {
  const candidates = realtimePayloadCandidates(raw)
  let best: AppNotification | null = null
  let bestScore = -1

  for (const candidate of candidates) {
    const normalized = normalizeNotificationInternal(candidate)

    if (normalized) {
      const score = scoreRealtimeCandidate(normalized)

      if (score > bestScore) {
        best = normalized
        bestScore = score
      }
    }
  }

  return best
}

export const normalizeMeta = (raw: unknown): NotificationMeta | null => {
  const meta = toRecord(raw)

  if (!meta) {
    return null
  }

  const currentPage = toPositiveInt(meta.current_page)
  const lastPage = toPositiveInt(meta.last_page)
  const perPage = toPositiveInt(meta.per_page)
  const total = toPositiveInt(meta.total)

  if (!currentPage || !lastPage || !perPage || total === null) {
    return null
  }

  return {
    current_page: currentPage,
    last_page: lastPage,
    per_page: perPage,
    total,
  }
}

const NotificationService = {
  async getNotifications(params: NotificationListParams = {}) {
    const { data } = await http.get('/notifications', { params })
    return data
  },

  async getUnreadCount(): Promise<number> {
    const { data } = await http.get('/notifications/unread-count')
    const payload = toRecord(data)
    const nestedData = toRecord(payload?.data)

    return Math.max(0, Math.trunc(Number(nestedData?.count ?? 0)))
  },

  async markRead(id: string) {
    const { data } = await http.post(`/notifications/${id}/read`)
    return data
  },

  async markAllRead() {
    const { data } = await http.post('/notifications/read-all')
    return data
  },

  subscribeToUserNotifications(
    userId: number,
    onNotification: (payload: unknown) => void,
  ): () => void {
    const normalizedUserId = Math.trunc(Number(userId))
    const channelName = `users.${normalizedUserId}`

    const echo = getEcho()
    const channel = echo.private(channelName) as unknown as NotificationChannel

    const handler = (payload: unknown): void => {
      onNotification(payload)
    }

    if (typeof channel.notification === 'function') {
      channel.notification(handler)
    }

    channel.listen(USER_NOTIFICATION_EVENT, handler)
    channel.listen(USER_NOTIFICATION_EVENT_ALT, handler)

    return () => {
      channel.stopListening(USER_NOTIFICATION_EVENT, handler)
      channel.stopListening(USER_NOTIFICATION_EVENT_ALT, handler)
      echo.leave(channelName)
    }
  },
}

export default NotificationService

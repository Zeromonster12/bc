import { defineStore } from 'pinia'
import { useMessageStore } from '@/stores/message'
import NotificationService, {
  normalizeApiNotification,
  normalizeMeta,
  normalizeRealtimeNotification,
  type AppNotification,
  type NotificationMeta,
} from '@/services/notifications/NotificationService'

const DEFAULT_PER_PAGE = 20

let realtimeTeardown: (() => void) | null = null
let realtimeStartPromise: Promise<void> | null = null
let syncTimeoutId: ReturnType<typeof setTimeout> | null = null
let latestRefreshRequestId = 0

const toPositiveInt = (value: unknown): number | null => {
  const numeric = Number(value)

  if (!Number.isFinite(numeric) || numeric <= 0) {
    return null
  }

  return Math.trunc(numeric)
}

const notificationSemanticKey = (notification: AppNotification): string => {
  const messageId = toPositiveInt(notification.data.message_id)

  if (notification.type === 'message.received' && messageId) {
    return `message.received:${messageId}`
  }

  const changedAt = String(notification.data.changed_at ?? '').trim()

  if (
    (notification.type === 'company.approved' || notification.type === 'company.rejected') &&
    changedAt !== ''
  ) {
    const userId = toPositiveInt(notification.data.user_id) ?? 0
    return `${notification.type}:${userId}:${changedAt}`
  }

  return `id:${notification.id}`
}

const mergeNotifications = (
  incomingRows: AppNotification[],
  existingRows: AppNotification[],
): AppNotification[] => {
  const merged = new Map<string, AppNotification>()

  incomingRows.forEach((row) => {
    merged.set(notificationSemanticKey(row), row)
  })

  existingRows.forEach((row) => {
    const key = notificationSemanticKey(row)

    if (!merged.has(key)) {
      merged.set(key, row)
    }
  })

  return Array.from(merged.values())
    .sort((a, b) => b.created_at.localeCompare(a.created_at))
    .slice(0, 100)
}

interface NotificationState {
  notifications: AppNotification[]
  pagination: NotificationMeta | null
  unreadCount: number
  loading: boolean
  loadingMore: boolean
  initialized: boolean
  subscribedUserId: number | null
}

export const useNotificationStore = defineStore('notification', {
  state: (): NotificationState => ({
    notifications: [],
    pagination: null,
    unreadCount: 0,
    loading: false,
    loadingMore: false,
    initialized: false,
    subscribedUserId: null,
  }),

  getters: {
    hasUnread: (state): boolean => state.unreadCount > 0,
  },

  actions: {
    async initialize(userId: number): Promise<void> {
      const normalizedUserId = Math.trunc(Number(userId))

      if (!Number.isFinite(normalizedUserId) || normalizedUserId <= 0) {
        this.reset()
        return
      }

      const hasActiveRealtimeSubscription = realtimeTeardown !== null

      if (
        this.initialized &&
        this.subscribedUserId === normalizedUserId &&
        hasActiveRealtimeSubscription
      ) {
        return
      }

      this.teardownRealtime()
      this.subscribedUserId = normalizedUserId
      this.initialized = true

      try {
        await Promise.all([this.fetchUnreadCount(), this.fetchNotifications(1)])
      } catch {
      } finally {
        await this.startRealtime(normalizedUserId)
      }
    },

    async startRealtime(userId: number): Promise<void> {
      const normalizedUserId = Math.trunc(Number(userId))

      if (!Number.isFinite(normalizedUserId) || normalizedUserId <= 0) {
        return
      }

      if (realtimeStartPromise) {
        await realtimeStartPromise
      }

      realtimeStartPromise = (async () => {
        this.teardownRealtime()

        try {
          realtimeTeardown = await NotificationService.subscribeToUserNotifications(
            normalizedUserId,
            (payload) => {
              this.handleRealtimeNotification(payload)
            },
          )
        } catch {
          realtimeTeardown = null
        }
      })()

      try {
        await realtimeStartPromise
      } finally {
        realtimeStartPromise = null
      }
    },

    teardownRealtime(): void {
      if (realtimeTeardown) {
        realtimeTeardown()
        realtimeTeardown = null
      }
    },

    reset(): void {
      this.teardownRealtime()
      if (syncTimeoutId) {
        clearTimeout(syncTimeoutId)
        syncTimeoutId = null
      }
      this.notifications = []
      this.pagination = null
      this.unreadCount = 0
      this.loading = false
      this.loadingMore = false
      this.initialized = false
      this.subscribedUserId = null
    },

    async reconnectRealtime(): Promise<void> {
      const userId = Number(this.subscribedUserId ?? 0)

      if (!Number.isFinite(userId) || userId <= 0) {
        return
      }

      await this.startRealtime(userId)
      void this.fetchUnreadCount()
    },

    scheduleListSync(delayMs = 250): void {
      if (syncTimeoutId) {
        clearTimeout(syncTimeoutId)
      }

      syncTimeoutId = setTimeout(() => {
        syncTimeoutId = null
        void Promise.all([this.fetchNotifications(1), this.fetchUnreadCount()])
      }, Math.max(0, Math.trunc(delayMs)))
    },

    async fetchNotifications(page = 1): Promise<void> {
      const append = page > 1
      const refreshRequestId = append ? 0 : ++latestRefreshRequestId

      if (append) {
        if (this.loadingMore) {
          return
        }

        this.loadingMore = true
      } else {
        this.loading = true
      }

      try {
        const response = await NotificationService.getNotifications({
          page,
          per_page: DEFAULT_PER_PAGE,
        })

        const rowsRaw = Array.isArray((response as { data?: unknown }).data)
          ? ((response as { data?: unknown[] }).data ?? [])
          : []

        const rows = rowsRaw
          .map((row) => normalizeApiNotification(row))
          .filter((row): row is AppNotification => row !== null)

        if (!append && refreshRequestId !== latestRefreshRequestId) {
          return
        }

        if (!append) {
          this.notifications = mergeNotifications(rows, this.notifications)
        } else {
          this.notifications = mergeNotifications(rows, this.notifications)
        }

        this.pagination = normalizeMeta((response as { meta?: unknown }).meta)
      } finally {
        if (append) {
          this.loadingMore = false
        } else {
          this.loading = false
        }
      }
    },

    async loadMore(): Promise<void> {
      if (!this.pagination) {
        return
      }

      if (this.pagination.current_page >= this.pagination.last_page) {
        return
      }

      await this.fetchNotifications(this.pagination.current_page + 1)
    },

    async fetchUnreadCount(): Promise<void> {
      try {
        this.unreadCount = await NotificationService.getUnreadCount()
      } catch {
      }
    },

    handleRealtimeNotification(payload: unknown): void {
      const incoming = normalizeRealtimeNotification(payload)

      if (!incoming) {
        this.scheduleListSync(120)
        return
      }

      const messageStore = useMessageStore()
      const activeConversationId = Number(
        (messageStore.currentConversation as { id?: number } | null)?.id ?? 0,
      )
      const notificationConversationId = Number(incoming.data.conversation_id ?? 0)

      const shouldAutoRead =
        incoming.type === 'message.received' &&
        incoming.read_at === null &&
        activeConversationId > 0 &&
        notificationConversationId > 0 &&
        activeConversationId === notificationConversationId

      if (shouldAutoRead) {
        incoming.read_at = new Date().toISOString()
        this.upsertNotification(incoming, false)

        if (!incoming.id.startsWith('realtime-')) {
          void this.markRead(incoming.id, true)
        }

        this.scheduleListSync(250)

        return
      }

      this.upsertNotification(incoming, incoming.read_at === null)
      this.scheduleListSync(250)
    },

    upsertNotification(notification: AppNotification, bumpUnread: boolean): void {
      const notificationKey = notificationSemanticKey(notification)
      const index = this.notifications.findIndex(
        (row) => notificationSemanticKey(row) === notificationKey,
      )

      if (index === -1) {
        this.notifications.unshift(notification)
        this.notifications = this.notifications
          .slice(0, 100)
          .sort((a, b) => b.created_at.localeCompare(a.created_at))

        if (bumpUnread && notification.read_at === null) {
          this.unreadCount += 1
        }

        return
      }

      const current = this.notifications[index]

      if (!current) {
        return
      }

      const wasUnread = current.read_at === null

      const updated: AppNotification = {
        ...current,
        ...notification,
        id:
          !notification.id.startsWith('realtime-') || current.id.startsWith('realtime-')
            ? notification.id
            : current.id,
        data: {
          ...current.data,
          ...notification.data,
        },
      }

      this.notifications[index] = updated
      this.notifications.sort((a, b) => b.created_at.localeCompare(a.created_at))

      const isUnread = updated.read_at === null

      if (wasUnread && !isUnread) {
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      }

      if (!wasUnread && isUnread && bumpUnread) {
        this.unreadCount += 1
      }
    },

    async markRead(id: string, silent = false): Promise<void> {
      if (typeof id !== 'string' || id.trim() === '') {
        return
      }

      const index = this.notifications.findIndex((row) => row.id === id)
      let wasUnread = false

      if (index !== -1) {
        const target = this.notifications[index]

        if (target && target.read_at === null) {
          wasUnread = true
          target.read_at = new Date().toISOString()
          this.unreadCount = Math.max(0, this.unreadCount - 1)
        }
      }

      if (id.startsWith('realtime-')) {
        return
      }

      try {
        const response = await NotificationService.markRead(id)
        const normalized = normalizeApiNotification((response as { data?: unknown }).data)

        if (normalized) {
          this.upsertNotification(normalized, false)
        }
      } catch {
        if (wasUnread) {
          this.unreadCount += 1

          const target = this.notifications[index]
          if (target) {
            target.read_at = null
          }
        }
      } finally {
        if (!silent) {
          void this.fetchUnreadCount()
        }
      }
    },

    async markAllRead(): Promise<void> {
      const hadUnread = this.unreadCount > 0
      const nowIso = new Date().toISOString()

      if (hadUnread) {
        this.notifications = this.notifications.map((notification) =>
          notification.read_at === null
            ? {
                ...notification,
                read_at: nowIso,
              }
            : notification,
        )

        this.unreadCount = 0
      }

      try {
        await NotificationService.markAllRead()
      } catch {
        if (hadUnread) {
          void this.fetchNotifications(1)
        }
      } finally {
        void this.fetchUnreadCount()
      }
    },

    async markConversationRead(conversationId: number): Promise<void> {
      const normalizedConversationId = Math.trunc(Number(conversationId))

      if (!Number.isFinite(normalizedConversationId) || normalizedConversationId <= 0) {
        return
      }

      const unreadConversationNotifications = this.notifications.filter((notification) => {
        if (notification.type !== 'message.received' || notification.read_at !== null) {
          return false
        }

        return Number(notification.data.conversation_id ?? 0) === normalizedConversationId
      })

      if (unreadConversationNotifications.length === 0) {
        return
      }

      await Promise.all(
        unreadConversationNotifications.map((notification) => this.markRead(notification.id, true)),
      )

      void this.fetchUnreadCount()
    },
  },
})

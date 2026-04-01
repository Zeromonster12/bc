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

      if (this.initialized && this.subscribedUserId === normalizedUserId) {
        return
      }

      this.teardownRealtime()
      this.subscribedUserId = normalizedUserId
      this.initialized = true

      await Promise.all([this.fetchUnreadCount(), this.fetchNotifications(1)])
      this.startRealtime(normalizedUserId)
    },

    startRealtime(userId: number): void {
      this.teardownRealtime()

      realtimeTeardown = NotificationService.subscribeToUserNotifications(userId, (payload) => {
        this.handleRealtimeNotification(payload)
      })
    },

    teardownRealtime(): void {
      if (realtimeTeardown) {
        realtimeTeardown()
        realtimeTeardown = null
      }
    },

    reset(): void {
      this.teardownRealtime()
      this.notifications = []
      this.pagination = null
      this.unreadCount = 0
      this.loading = false
      this.loadingMore = false
      this.initialized = false
      this.subscribedUserId = null
    },

    async fetchNotifications(page = 1): Promise<void> {
      const append = page > 1

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

        if (!append) {
          this.notifications = rows
        } else {
          const merged = new Map(this.notifications.map((row) => [row.id, row] as const))

          rows.forEach((row) => {
            merged.set(row.id, row)
          })

          this.notifications = Array.from(merged.values()).sort((a, b) =>
            b.created_at.localeCompare(a.created_at),
          )
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
        void this.fetchUnreadCount()
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

        return
      }

      this.upsertNotification(incoming, incoming.read_at === null)
    },

    upsertNotification(notification: AppNotification, bumpUnread: boolean): void {
      const index = this.notifications.findIndex((row) => row.id === notification.id)

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
  },
})

<template>
  <aside class="fixed inset-y-0 right-0 z-80 flex w-full max-w-md flex-col overflow-hidden border-l border-slate-200/80 bg-white/95 shadow-[0_18px_40px_rgba(15,23,42,0.16)] backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-[0_18px_40px_rgba(2,6,23,0.55)] sm:inset-y-3 sm:right-3 sm:max-h-[calc(100vh-1.5rem)] sm:rounded-3xl sm:border">
    <header class="flex items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 py-3.5 dark:border-slate-700/80 dark:bg-slate-900/90">
      <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Notifications</h3>
        <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ unreadCount }} unread</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-2xl border border-slate-200/80 bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-slate-800"
          :disabled="unreadCount <= 0"
          @click="$emit('mark-all-read')"
        >
          Mark all read
        </button>

        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-2xl border border-slate-200/80 bg-white/90 text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300 dark:hover:bg-slate-800"
          aria-label="Close notifications"
          @click="$emit('close')"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </header>

    <div v-if="loading" class="flex flex-1 items-center justify-center bg-slate-50/70 px-6 dark:bg-slate-900/75">
      <p class="text-sm text-slate-500 dark:text-slate-400">Loading notifications...</p>
    </div>

    <div v-else-if="notifications.length === 0" class="flex flex-1 items-center justify-center bg-slate-50/70 px-6 dark:bg-slate-900/75">
      <p class="text-center text-sm text-slate-500 dark:text-slate-400">No notifications yet.</p>
    </div>

    <div v-else class="message-scrollbar flex-1 overflow-y-auto bg-slate-50/70 px-2 py-2 dark:bg-slate-900/75">
      <ul class="space-y-1.5">
        <li v-for="notification in notifications" :key="notification.id">
          <button
            type="button"
            class="group w-full rounded-2xl border px-3.5 py-3 text-left transition"
            :class="
              notification.read_at
                ? 'border-slate-200/80 bg-white/90 hover:bg-white dark:border-slate-700/70 dark:bg-slate-900/90 dark:hover:bg-slate-900'
                : 'border-indigo-200/80 bg-indigo-50/70 shadow-[0_6px_16px_rgba(79,51,215,0.08)] hover:border-indigo-300 dark:border-indigo-500/35 dark:bg-indigo-500/12 dark:hover:bg-indigo-500/18'
            "
            @click="handleNotificationClick(notification)"
          >
            <div class="mb-1.5 flex items-start gap-2">
              <span
                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                :class="notification.read_at ? 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'"
              >
                <component :is="iconForType(notification.type)" class="h-4 w-4" />
              </span>
              <span
                class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                :class="notification.read_at ? 'bg-slate-300 dark:bg-slate-600' : metaForType(notification.type).dotClass"
              />
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ notification.title }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                  {{ formatTime(notification.created_at) }}
                </p>
              </div>
            </div>

            <p class="line-clamp-2 text-[13px] text-slate-700 dark:text-slate-200">
              {{ notification.body || 'Open notification' }}
            </p>

            <p
              v-if="metaForType(notification.type).actionLabel !== ''"
              class="mt-1.5 text-[11px] font-semibold"
              :class="metaForType(notification.type).accentTextClass"
            >
              {{ metaForType(notification.type).actionLabel }}
            </p>
          </button>
        </li>
      </ul>

      <div v-if="hasMore" class="border-t border-slate-200/80 bg-white/90 p-3 dark:border-slate-700/80 dark:bg-slate-900/90">
        <button
          type="button"
          class="w-full rounded-2xl border border-slate-200/80 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
          :disabled="loadingMore"
          @click="$emit('load-more')"
        >
          {{ loadingMore ? 'Loading...' : 'Load more' }}
        </button>
      </div>
    </div>
  </aside>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { Bell, CheckCircle2, MessageSquare, X, XCircle } from 'lucide-vue-next'
import {
  resolveNotificationUiMeta,
  type AppNotification,
  type NotificationUiMeta,
} from '@/services/notifications/NotificationService'

export default defineComponent({
  name: 'NotificationPanel',
  components: {
    Bell,
    CheckCircle2,
    MessageSquare,
    XCircle,
    X,
  },
  props: {
    notifications: {
      type: Array as PropType<AppNotification[]>,
      default: () => [],
    },
    unreadCount: {
      type: Number,
      default: 0,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    loadingMore: {
      type: Boolean,
      default: false,
    },
    hasMore: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['close', 'mark-read', 'mark-all-read', 'load-more', 'open-notification'],
  methods: {
    metaForType(type: string): NotificationUiMeta {
      return resolveNotificationUiMeta(type)
    },
    iconForType(type: string): 'Bell' | 'MessageSquare' | 'CheckCircle2' | 'XCircle' {
      return this.metaForType(type).icon
    },
    formatTime(value: string): string {
      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return ''
      }

      return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    handleNotificationClick(notification: AppNotification): void {
      this.$emit('mark-read', notification.id)
      this.$emit('open-notification', notification)
    },
  },
})
</script>

<style scoped>
.message-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.message-scrollbar::-webkit-scrollbar {
  width: 10px;
}

.message-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.message-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 9999px;
  border: 2px solid transparent;
  background-clip: content-box;
}

.message-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}

:global(.dark) .message-scrollbar {
  scrollbar-color: #475569 transparent;
}

:global(.dark) .message-scrollbar::-webkit-scrollbar-thumb {
  background-color: #475569;
}

:global(.dark) .message-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #64748b;
}
</style>

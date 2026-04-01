<template>
  <div class="min-h-screen bg-transparent">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur lg:hidden dark:border-slate-700/80 dark:bg-slate-900/95">
      <div class="flex h-14 items-center justify-between px-4">
        <RouterLink
          to="/projects"
          class="inline-flex items-center rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-sm font-semibold tracking-tight text-[#312a55] dark:border-slate-700/80 dark:bg-slate-900 dark:text-slate-100"
        >
          Project Linker
        </RouterLink>

        <button
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
          @click="sidebarOpen = !sidebarOpen"
          :aria-label="sidebarOpen ? 'Close menu' : 'Open menu'"
          :aria-expanded="sidebarOpen"
        >
          <svg v-if="!sidebarOpen" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
          </svg>
          <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
          </svg>
        </button>
      </div>
    </header>

    <div class="flex min-h-screen">
      <aside class="hidden self-start p-4 lg:sticky lg:top-0 lg:block lg:h-screen">
        <Sidebar :collapsed="true" />
      </aside>

      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="sidebarOpen"
          class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden dark:bg-slate-950/60"
          @click="sidebarOpen = false"
        />
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-200"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-150"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
      >
        <aside v-if="sidebarOpen" class="fixed left-0 top-14 z-60 h-[calc(100%-3.5rem)] lg:hidden">
          <Sidebar mobile @navigate="sidebarOpen = false" />
        </aside>
      </Transition>

      <div class="flex-1 flex flex-col">
        <main class="flex-1 p-4 pt-18 sm:p-6 sm:pt-20 lg:p-8 lg:pt-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'

export default defineComponent({
  name: 'AppLayout',
  components: { Sidebar },
  setup() {
    return {
      auth: useAuthStore(),
      notificationStore: useNotificationStore(),
    }
  },
  data() {
    return {
      sidebarOpen: false,
    }
  },
  methods: {
    async initializeNotifications(): Promise<void> {
      const userId = Number(this.auth.user?.id ?? 0)

      if (!Number.isFinite(userId) || userId <= 0) {
        this.notificationStore.reset()
        return
      }

      await this.notificationStore.initialize(userId)
    },
    async reconnectNotificationsIfVisible(): Promise<void> {
      if (document.visibilityState !== 'visible') {
        return
      }

      await this.notificationStore.reconnectRealtime()
    },
    async reconnectNotifications(): Promise<void> {
      await this.notificationStore.reconnectRealtime()
    },
  },
  mounted() {
    void this.initializeNotifications()
    document.addEventListener('visibilitychange', this.reconnectNotificationsIfVisible)
    window.addEventListener('focus', this.reconnectNotifications)
    window.addEventListener('online', this.reconnectNotifications)
  },
  watch: {
    sidebarOpen(isOpen: boolean) {
      document.body.classList.toggle('overflow-hidden', isOpen)
    },
    'auth.user.id'() {
      void this.initializeNotifications()
    },
    'auth.user.role'() {
      void this.initializeNotifications()
    },
  },
  beforeUnmount() {
    document.body.classList.remove('overflow-hidden')
    document.removeEventListener('visibilitychange', this.reconnectNotificationsIfVisible)
    window.removeEventListener('focus', this.reconnectNotifications)
    window.removeEventListener('online', this.reconnectNotifications)
  },
})
</script>

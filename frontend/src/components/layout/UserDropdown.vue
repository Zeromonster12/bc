<template>
  <div class="relative" ref="container">
    <button
      class="flex items-center gap-2 text-sm font-medium text-gray-700 transition hover:text-gray-900 dark:text-slate-300 dark:hover:text-slate-100"
      @click="open = !open"
    >
      <div
        class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
      >
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt="User avatar"
          class="h-full w-full object-cover"
          @error="handleAvatarError"
        />
        <span v-else>{{ initials }}</span>
      </div>
      <span class="hidden md:block">{{ auth.user?.name }}</span>
      <svg class="h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition name="dropdown">
      <div
        v-if="open"
        class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-100 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
      >
        <div class="border-b border-gray-100 px-4 py-2 dark:border-slate-700">
          <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ auth.user?.name }}</p>
          <p class="text-xs text-gray-500 dark:text-slate-400">{{ auth.user?.email }}</p>
        </div>
        <RouterLink
          :to="profileRoute"
          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="open = false"
        >
          Profile
        </RouterLink>
        <button
          class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
          @click="handleLogout"
        >
          Sign out
        </button>
      </div>
    </Transition>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { resolveAssetUrl } from '@/services/core/url'

export default defineComponent({
  name: 'UserDropdown',
  setup() {
    return { auth: useAuthStore() }
  },
  data() {
    return {
      open: false,
      avatarRefreshAttempted: false,
      avatarLoadFailed: false,
    }
  },
  computed: {
    initials(): string {
      return (this.auth.user?.name ?? 'U')
        .split(' ')
        .map((p: string) => p[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
    avatarUrl(): string {
      if (this.avatarLoadFailed) return ''
      return resolveAssetUrl(this.auth.user?.avatar_url)
    },
    profileRoute(): string {
      if (this.auth.isStudent) return '/profile/student'
      if (this.auth.isCompany) return '/profile/company'
      return '/dashboard'
    },
  },
  mounted() {
    document.addEventListener('click', this.handleOutsideClick)
  },
  watch: {
    'auth.user.avatar_url'() {
      this.avatarRefreshAttempted = false
      this.avatarLoadFailed = false
    },
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
  },
  methods: {
    async handleAvatarError() {
      if (this.avatarRefreshAttempted) {
        this.avatarLoadFailed = true
        return
      }

      this.avatarRefreshAttempted = true
      const refreshed = await this.auth.fetchUserSilently()
      if (!refreshed) {
        this.avatarLoadFailed = true
      }
    },
    async handleLogout() {
      this.open = false
      await this.auth.logout()
      this.$router.push({ name: 'login' })
    },
    handleOutsideClick(e: MouseEvent) {
      const el = this.$refs.container as HTMLElement
      if (el && !el.contains(e.target as Node)) {
        this.open = false
      }
    },
  },
})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

<template>
  <div class="relative" ref="container">
    <button
      class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition"
      @click="open = !open"
    >
      <div
        class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm overflow-hidden"
      >
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt="User avatar"
          class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
      </div>
      <span class="hidden md:block">{{ auth.user?.name }}</span>
      <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition name="dropdown">
      <div
        v-if="open"
        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 z-50 py-1"
      >
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-sm font-medium text-gray-900">{{ auth.user?.name }}</p>
          <p class="text-xs text-gray-500">{{ auth.user?.email }}</p>
        </div>
        <RouterLink
          :to="profileRoute"
          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
          @click="open = false"
        >
          Profile
        </RouterLink>
        <button
          class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
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
    return { open: false }
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
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
  },
  methods: {
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

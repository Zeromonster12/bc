<template>
  <nav
    :class="[
      'h-full flex flex-col border-r border-slate-200/80 bg-white/95 backdrop-blur',
      mobile ? 'w-80 max-w-[86vw]' : 'w-72',
    ]"
  >
    <div class="px-6 py-6 border-b border-slate-200/80">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">BC Platform</p>
      <span class="mt-1 block text-xl font-semibold text-slate-900">Control Panel</span>
    </div>

    <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <RouterLink
        v-for="link in filteredLinks"
        :key="link.name"
        :to="{ name: link.name }"
        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
        :class="
          isActive(link.name)
            ? 'bg-teal-50 text-teal-800 ring-1 ring-teal-100'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        "
        @click="mobile && $emit('navigate')"
      >
        <span
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold"
          :class="
            isActive(link.name)
              ? 'bg-teal-600 text-white'
              : 'bg-slate-200 text-slate-600 group-hover:bg-slate-300'
          "
        >
          {{ link.short }}
        </span>
        {{ link.label }}
      </RouterLink>
    </div>

    <div class="px-4 py-4 border-t border-slate-200/80">
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-xl bg-teal-100 flex items-center justify-center text-teal-700 font-semibold text-sm overflow-hidden"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt="User avatar"
            class="h-full w-full object-cover"
          />
          <span v-else>{{ userInitials }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-900 truncate">{{ auth.user?.name }}</p>
          <p class="text-xs text-slate-500 capitalize">{{ auth.user?.role }}</p>
        </div>
      </div>
    </div>
  </nav>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { resolveAssetUrl } from '@/services/core/url'

interface NavLink {
  name: string
  label: string
  short: string
  roles: string[]
}

export default defineComponent({
  name: 'Sidebar',
  props: {
    mobile: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['navigate'],
  setup() {
    return { auth: useAuthStore() }
  },
  data(): { links: NavLink[] } {
    return {
      links: [
        {
          name: 'dashboard',
          label: 'Dashboard',
          short: 'DB',
          roles: ['student', 'company', 'admin'],
        },
        {
          name: 'projects',
          label: 'Projects',
          short: 'PR',
          roles: ['student', 'company', 'admin'],
        },
        { name: 'projects.create', label: 'Post Project', short: 'NP', roles: ['company'] },
        { name: 'applications', label: 'Applications', short: 'AP', roles: ['student', 'company'] },
        {
          name: 'applications.accepted',
          label: 'Accepted Projects',
          short: 'AC',
          roles: ['student'],
        },
        { name: 'profile.student', label: 'My Profile', short: 'MP', roles: ['student'] },
        { name: 'profile.company', label: 'Company Profile', short: 'CP', roles: ['company'] },
        { name: 'messages', label: 'Messages', short: 'MS', roles: ['student', 'company'] },
        { name: 'admin', label: 'Admin Panel', short: 'AD', roles: ['admin'] },
      ],
    }
  },
  computed: {
    filteredLinks(): NavLink[] {
      const role = this.auth.user?.role ?? ''
      return this.links.filter((l) => l.roles.includes(role))
    },
    userInitials(): string {
      return (this.auth.user?.name ?? 'U')
        .split(' ')
        .map((p) => p[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
    avatarUrl(): string {
      return resolveAssetUrl(this.auth.user?.avatar_url)
    },
  },
  methods: {
    isActive(routeName: string): boolean {
      return this.$route.name === routeName
    },
  },
})
</script>

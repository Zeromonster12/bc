<template>
  <nav
    @mouseleave="onSidebarLeave"
    :class="[
      'h-full flex flex-col overflow-visible bg-white/95 backdrop-blur transition-all duration-200 dark:bg-slate-900/95',
      mobile
        ? 'w-72 max-w-[86vw] border-r border-slate-200/80 dark:border-slate-700/80'
        : collapsed
          ? isHoverExpanded
            ? 'w-64 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]'
            : 'w-16 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]'
          : 'w-64 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]',
    ]"
  >
    <div class="flex-1 overflow-y-auto overflow-x-visible px-3 py-4 space-y-1">
      <RouterLink
        v-for="link in filteredLinks"
        :key="link.name"
        :to="{ name: link.name }"
        :class="[
          'group relative flex items-center rounded-2xl text-sm font-medium transition-all duration-200',
          collapsed && !mobile
            ? isHoverExpanded
              ? 'h-9 w-full justify-start px-3'
              : 'mx-auto h-9 w-9 justify-center overflow-hidden p-0'
            : 'gap-2.5 px-3 py-2',
          isActive(link.name)
            ? 'bg-[#4e3aba] text-white ring-1 ring-[#4e3aba]'
            : 'text-slate-600 hover:bg-slate-100 hover:text-[#4e3aba] dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-[#a895ff]',
        ]"
        @mouseenter="onLinkEnter(link.name)"
        @mouseleave="onLinkLeave"
        @click="mobile && $emit('navigate')"
      >
        <span
          class="inline-flex h-7 w-7 items-center justify-center"
          :class="isActive(link.name) ? 'text-white' : 'text-slate-500 group-hover:text-[#4e3aba] dark:text-slate-400 dark:group-hover:text-[#a895ff]'"
        >
          <component :is="link.icon" class="h-4 w-4" />
        </span>
        <span
          :class="[
            'truncate whitespace-nowrap transition-all duration-200',
            collapsed && !mobile
              ? isHoverExpanded
                ? 'ml-2 max-w-42 opacity-100'
                : 'max-w-0 opacity-0'
              : '',
          ]"
        >
          {{ link.label }}
        </span>
      </RouterLink>

    </div>

    <div class="px-3 pb-0">
      <button
        type="button"
        :class="[
          'group flex items-center rounded-2xl text-sm font-medium transition-all duration-200 text-slate-600 hover:bg-slate-100 hover:text-[#4e3aba] dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-[#a895ff]',
          collapsed && !mobile
            ? isHoverExpanded
              ? 'h-9 w-full justify-start px-3'
              : 'mx-auto h-9 w-9 justify-center overflow-hidden p-0'
            : 'w-full gap-2.5 px-3 py-2',
        ]"
        @click="toggleTheme"
        :aria-label="themeStore.resolvedTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
      >
        <span class="inline-flex h-7 w-7 items-center justify-center text-slate-500 group-hover:text-[#4e3aba] dark:text-slate-400 dark:group-hover:text-[#a895ff]">
          <Moon v-if="themeStore.resolvedTheme === 'dark'" class="h-4 w-4" />
          <Sun v-else class="h-4 w-4" />
        </span>
        <span
          :class="[
            'truncate whitespace-nowrap transition-all duration-200',
            collapsed && !mobile
              ? isHoverExpanded
                ? 'ml-2 max-w-42 opacity-100'
                : 'max-w-0 opacity-0'
              : '',
          ]"
        >
          {{ themeStore.resolvedTheme === 'dark' ? 'Dark Mode' : 'Light Mode' }}
        </span>
      </button>
    </div>

    <div
      ref="profileMenuContainer"
      class="relative p-3"
      @mouseenter="onLinkEnter('__profile__')"
      @mouseleave="onLinkLeave"
    >
      <button
        type="button"
        :class="[
          'group flex w-full items-center rounded-2xl transition-all duration-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          collapsed && !mobile
            ? isHoverExpanded
              ? 'h-11 w-full justify-start px-3'
              : 'mx-auto h-9 w-9 justify-center p-0'
            : 'h-11 gap-2.5 px-3',
        ]"
        @click="profileMenuOpen = !profileMenuOpen"
      >
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
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
        <div
          :class="[
            'min-w-0 transition-all duration-200',
            collapsed && !mobile
              ? isHoverExpanded
                ? 'ml-2 max-w-40 opacity-100'
                : 'max-w-0 opacity-0'
              : 'max-w-40 opacity-100',
          ]"
        >
          <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ auth.user?.name }}</p>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth.user?.email }}</p>
        </div>
      </button>

      <Transition name="dropdown">
        <div
          v-if="profileMenuOpen"
          class="absolute bottom-full left-3 right-3 z-50 mb-2 rounded-lg border border-gray-100 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
        >
          <div class="border-b border-gray-100 px-4 py-2 dark:border-slate-700">
            <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">{{ auth.user?.email }}</p>
          </div>
          <RouterLink
            :to="profileRoute"
            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="
              profileMenuOpen = false;
              mobile && $emit('navigate')
            "
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
  </nav>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import {
  BriefcaseBusiness,
  Building2,
  CheckCircle2,
  FolderKanban,
  KanbanSquare,
  LayoutDashboard,
  MessageSquare,
  Moon,
  Shield,
  Sun,
  User,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { resolveAssetUrl } from '@/services/core/url'
import { useThemeStore } from '@/stores/theme'

interface NavLink {
  name: string
  label: string
  icon: string
  roles: string[]
}

export default defineComponent({
  name: 'Sidebar',
  components: {
    LayoutDashboard,
    FolderKanban,
    BriefcaseBusiness,
    Building2,
    CheckCircle2,
    KanbanSquare,
    User,
    MessageSquare,
    Moon,
    Shield,
    Sun,
  },
  props: {
    mobile: {
      type: Boolean,
      default: false,
    },
    collapsed: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['navigate'],
  setup() {
    return {
      auth: useAuthStore(),
      themeStore: useThemeStore(),
    }
  },
  data(): {
    links: NavLink[]
    hoveredLink: string | null
    hoverCloseTimeoutId: number | null
    hoverCloseDelayMs: number
    profileMenuOpen: boolean
    avatarRefreshAttempted: boolean
    avatarLoadFailed: boolean
  } {
    return {
      hoveredLink: null,
      hoverCloseTimeoutId: null,
      hoverCloseDelayMs: 420,
      profileMenuOpen: false,
      avatarRefreshAttempted: false,
      avatarLoadFailed: false,
      links: [
        {
          name: 'dashboard',
          label: 'Dashboard',
          icon: 'LayoutDashboard',
          roles: ['student', 'company', 'admin'],
        },
        {
          name: 'projects',
          label: 'Projects',
          icon: 'FolderKanban',
          roles: ['student', 'company', 'admin'],
        },
        {
          name: 'projects.create',
          label: 'Post Project',
          icon: 'BriefcaseBusiness',
          roles: ['company'],
        },
        {
          name: 'applications',
          label: 'Applications',
          icon: 'Building2',
          roles: ['student', 'company'],
        },
        {
          name: 'applications.accepted',
          label: 'Accepted Projects',
          icon: 'CheckCircle2',
          roles: ['student'],
        },
        {
          name: 'applications.company-board',
          label: 'Task Board',
          icon: 'KanbanSquare',
          roles: ['company'],
        },
        { name: 'profile.student', label: 'My Profile', icon: 'User', roles: ['student'] },
        { name: 'profile.company', label: 'Company Profile', icon: 'User', roles: ['company'] },
        {
          name: 'messages',
          label: 'Messages',
          icon: 'MessageSquare',
          roles: ['student', 'company'],
        },
        { name: 'admin', label: 'Admin Panel', icon: 'Shield', roles: ['admin'] },
      ],
    }
  },
  computed: {
    filteredLinks(): NavLink[] {
      const role = this.auth.user?.role ?? ''
      return this.links.filter((l) => l.roles.includes(role))
    },
    isHoverExpanded(): boolean {
      return !this.mobile && this.collapsed && this.hoveredLink !== null
    },
    initials(): string {
      return (this.auth.user?.name ?? 'U')
        .split(' ')
        .map((part: string) => part[0])
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
  methods: {
    async handleAvatarError(): Promise<void> {
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
    toggleTheme(): void {
      this.themeStore.toggleTheme()
    },
    isActive(routeName: string): boolean {
      return this.$route.name === routeName
    },
    clearHoverCloseTimeout(): void {
      if (this.hoverCloseTimeoutId !== null) {
        window.clearTimeout(this.hoverCloseTimeoutId)
        this.hoverCloseTimeoutId = null
      }
    },
    onLinkEnter(routeName: string): void {
      if (!this.mobile && this.collapsed) {
        this.clearHoverCloseTimeout()
        this.hoveredLink = routeName
      }
    },
    onLinkLeave(): void {
      if (!this.mobile && this.collapsed) {
        if (this.profileMenuOpen) {
          return
        }
        this.clearHoverCloseTimeout()
        this.hoverCloseTimeoutId = window.setTimeout(() => {
          this.hoveredLink = null
          this.hoverCloseTimeoutId = null
        }, this.hoverCloseDelayMs)
        return
      }
      this.hoveredLink = null
    },
    onSidebarLeave(): void {
      if (this.profileMenuOpen) {
        this.profileMenuOpen = false
      }
      this.onLinkLeave()
    },
    async handleLogout(): Promise<void> {
      this.profileMenuOpen = false
      await this.auth.logout()
      this.$router.push({ name: 'login' })
    },
    handleOutsideClick(e: MouseEvent): void {
      const el = this.$refs.profileMenuContainer as HTMLElement | undefined
      if (el && !el.contains(e.target as Node)) {
        this.profileMenuOpen = false
      }
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
    this.clearHoverCloseTimeout()
    document.removeEventListener('click', this.handleOutsideClick)
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
  transform: translateY(4px);
}
</style>

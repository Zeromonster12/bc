<template>
  <nav
    @mouseleave="onSidebarLeave"
    :class="[
      'h-full flex flex-col overflow-x-hidden overflow-y-visible bg-white/95 backdrop-blur transition-all duration-200 dark:bg-slate-900/95',
      mobile
        ? 'w-72 max-w-[86vw] border-r border-slate-200/80 dark:border-slate-700/80'
        : collapsed
          ? isHoverExpanded
            ? 'w-64 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]'
            : 'w-16 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]'
          : 'w-64 rounded-3xl border border-slate-200/80 shadow-[0_8px_24px_rgba(15,23,42,0.06)] dark:border-slate-700/80 dark:shadow-[0_8px_24px_rgba(2,6,23,0.45)]',
    ]"
  >
    <div
      :class="[
        'px-3 pt-4 pb-2',
        collapsed && !mobile && !isHoverExpanded ? 'flex justify-center' : '',
      ]"
    >
      <RouterLink
        to="/projects"
        :class="[
          'inline-flex items-center rounded-2xl border border-slate-200/80 bg-white/80 text-[#312a55] transition hover:bg-slate-50 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:bg-slate-800',
          collapsed && !mobile && !isHoverExpanded
            ? 'h-9 w-9 justify-center text-xs font-extrabold tracking-wide'
            : 'px-3 py-2 text-sm font-semibold tracking-tight',
        ]"
        @mouseenter="onLinkEnter('__brand__')"
        @mouseleave="onLinkLeave"
      >
        {{ collapsed && !mobile && !isHoverExpanded ? 'PL' : 'Project Linker' }}
      </RouterLink>
    </div>

    <div class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-1">
      <div
        v-for="group in groupedLinks"
        :key="group.section"
        class="space-y-1"
      >
        <div class="relative h-6 overflow-hidden px-2">
          <p
            class="absolute inset-0 pt-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500 transition-opacity duration-200 dark:text-slate-400"
            :class="showSectionHeaders ? 'opacity-100' : 'pointer-events-none opacity-0'"
          >
            {{ group.section }}
          </p>

          <div
            class="absolute inset-0 flex items-center justify-center transition-opacity duration-200"
            :class="showSectionHeaders ? 'pointer-events-none opacity-0' : 'opacity-100'"
          >
            <span class="rounded-full border border-slate-200/80 bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-400">
              {{ compactSectionLabel(group.section) }}
            </span>
          </div>
        </div>

        <RouterLink
          v-for="link in group.links"
          :key="link.name"
          :to="link.to ?? { name: link.name }"
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
import type { RouteLocationRaw } from 'vue-router'
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
import ProfileService from '@/services/profile/ProfileService'
import { resolveAssetUrl } from '@/services/core/url'
import { useThemeStore } from '@/stores/theme'

interface NavLink {
  name: string
  label: string
  icon: string
  roles: string[]
  section: string
  to?: RouteLocationRaw
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
    companyLogoUrl: string
    companyLogoLoadedForUserId: number | null
  } {
    return {
      hoveredLink: null,
      hoverCloseTimeoutId: null,
      hoverCloseDelayMs: 420,
      profileMenuOpen: false,
      avatarRefreshAttempted: false,
      avatarLoadFailed: false,
      companyLogoUrl: '',
      companyLogoLoadedForUserId: null,
      links: [
        {
          name: 'dashboard',
          label: 'Dashboard',
          icon: 'LayoutDashboard',
          roles: ['student', 'company', 'admin'],
          section: 'Overview',
        },
        {
          name: 'projects',
          label: 'Projects',
          icon: 'FolderKanban',
          roles: ['student', 'admin'],
          section: 'Overview',
        },
        {
          name: 'projects.create',
          label: 'Create project',
          icon: 'BriefcaseBusiness',
          roles: ['company'],
          section: 'Work',
        },
        {
          name: 'projects',
          label: 'My projects',
          icon: 'FolderKanban',
          roles: ['company'],
          section: 'Work',
          to: { name: 'projects', query: { company: 'me' } },
        },
        {
          name: 'applications',
          label: 'Applications',
          icon: 'Building2',
          roles: ['student', 'company'],
          section: 'Work',
        },
        {
          name: 'applications.accepted',
          label: 'Accepted Projects',
          icon: 'CheckCircle2',
          roles: ['student'],
          section: 'Work',
        },
        {
          name: 'applications.company-board',
          label: 'Task Board',
          icon: 'KanbanSquare',
          roles: ['company'],
          section: 'Work',
        },
        {
          name: 'profile.student',
          label: 'My Profile',
          icon: 'User',
          roles: ['student'],
          section: 'Account',
        },
        {
          name: 'profile.company',
          label: 'Company Profile',
          icon: 'User',
          roles: ['company'],
          section: 'Account',
        },
        {
          name: 'messages',
          label: 'Messages',
          icon: 'MessageSquare',
          roles: ['student', 'company'],
          section: 'Account',
        },
        {
          name: 'admin',
          label: 'Admin Panel',
          icon: 'Shield',
          roles: ['admin'],
          section: 'Administration',
        },
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
    showSectionHeaders(): boolean {
      return this.mobile || !this.collapsed || this.isHoverExpanded
    },
    groupedLinks(): Array<{ section: string; links: NavLink[] }> {
      const grouped = this.filteredLinks.reduce(
        (acc, link) => {
          const sectionLinks = acc[link.section] ?? []
          sectionLinks.push(link)
          acc[link.section] = sectionLinks
          return acc
        },
        {} as Record<string, NavLink[]>,
      )

      const sectionOrder = ['Overview', 'Work', 'Account', 'Administration']
      return sectionOrder
        .filter((section) => (grouped[section] ?? []).length > 0)
        .map((section) => ({ section, links: grouped[section] ?? [] }))
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
      const userAvatar = resolveAssetUrl(this.auth.user?.avatar_url)
      if (userAvatar) return userAvatar

      if (this.auth.isCompany && this.companyLogoUrl) {
        return resolveAssetUrl(this.companyLogoUrl)
      }

      return ''
    },
    profileRoute(): string {
      if (this.auth.isStudent) return '/profile/student'
      if (this.auth.isCompany) return '/profile/company'
      return '/dashboard'
    },
  },
  methods: {
    async loadCompanyLogo(): Promise<void> {
      if (!this.auth.isCompany) {
        this.companyLogoUrl = ''
        this.companyLogoLoadedForUserId = null
        return
      }

      const userId = Number(this.auth.user?.id ?? 0)
      if (!Number.isFinite(userId) || userId <= 0) {
        return
      }

      if (this.companyLogoLoadedForUserId === userId) {
        return
      }

      try {
        const response = await ProfileService.getCompanyProfile()
        const logoCandidate = response?.data?.logo_url
        this.companyLogoUrl = typeof logoCandidate === 'string' ? logoCandidate : ''
      } catch {
        this.companyLogoUrl = ''
      } finally {
        this.companyLogoLoadedForUserId = userId
      }
    },
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
    compactSectionLabel(section: string): string {
      const labels: Record<string, string> = {
        Overview: 'OV',
        Work: 'WK',
        Account: 'AC',
        Administration: 'AD',
      }

      return labels[section] ?? section.slice(0, 2).toUpperCase()
    },
  },
  mounted() {
    document.addEventListener('click', this.handleOutsideClick)
    void this.loadCompanyLogo()
  },
  watch: {
    'auth.user.id'() {
      this.companyLogoLoadedForUserId = null
      void this.loadCompanyLogo()
    },
    'auth.user.role'() {
      this.companyLogoLoadedForUserId = null
      void this.loadCompanyLogo()
    },
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

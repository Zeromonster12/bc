<template>
  <header
    class="mx-4 mt-4 rounded-2xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm backdrop-blur sm:mx-6 sm:mt-6 sm:px-6 lg:mx-8"
  >
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 lg:hidden"
          @click="$emit('toggle-sidebar')"
          aria-label="Open menu"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
          </svg>
        </button>
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Workspace</p>
          <h2 class="text-lg font-semibold text-slate-900">{{ pageTitle }}</h2>
        </div>
      </div>
      <UserDropdown />
    </div>
  </header>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import UserDropdown from './UserDropdown.vue'

const ROUTE_TITLES: Record<string, string> = {
  dashboard: 'Dashboard',
  projects: 'Projects',
  'projects.create': 'Post a Project',
  'projects.show': 'Project Details',
  applications: 'Applications',
  'applications.accepted': 'Accepted Projects',
  'profile.student': 'My Profile',
  'profile.company': 'Company Profile',
  messages: 'Messages',
  admin: 'Admin Panel',
}

export default defineComponent({
  name: 'TopNav',
  components: { UserDropdown },
  emits: ['toggle-sidebar'],
  computed: {
    pageTitle(): string {
      return ROUTE_TITLES[this.$route.name as string] ?? 'BC Platform'
    },
  },
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3">
      <input
        v-model.trim="searchQuery"
        type="text"
        placeholder="Search by title or company..."
        class="rounded-full bg-[#e8e3f2] px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
      />

      <div class="relative admin-projects-dropdown">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full bg-[#e8e3f2] px-4 py-2.5 text-sm font-medium text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:ring-indigo-400/30"
          @click="toggleStatusDropdown"
        >
          {{ statusFilterLabel }}
          <ChevronDown class="h-4 w-4 transition" :class="statusDropdownOpen ? 'rotate-180' : ''" />
        </button>
        <div
          v-if="statusDropdownOpen"
          class="absolute z-20 mt-1 min-w-44 rounded-2xl bg-white p-1.5 dark:bg-slate-900"
        >
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setStatusFilter('')">All statuses</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setStatusFilter('draft')">Draft</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setStatusFilter('open')">Open</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setStatusFilter('closed')">Closed</button>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900/90">
      <div v-if="loading" class="space-y-2 p-4">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-170 w-full text-sm">
        <thead class="bg-[#f1edf8] text-xs font-semibold uppercase tracking-wide text-[#6b6682] dark:bg-slate-800/80 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3 text-left">Title</th>
            <th class="px-4 py-3 text-left">Company</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Created</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#ece8f4] dark:divide-slate-700/60">
          <tr v-if="visibleProjects.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-slate-400 dark:text-slate-500">
              {{ projects.length === 0 ? 'No projects found.' : 'No projects match this filter.' }}
            </td>
          </tr>
          <tr v-for="project in visibleProjects" :key="project.id" class="hover:bg-[#f7f4fc] dark:hover:bg-slate-800/70">
            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ project.title }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              <RouterLink
                v-if="project.company?.user_id"
                :to="`/companies/${project.company.user_id}/profile`"
                class="transition hover:text-indigo-600 dark:hover:text-indigo-300"
              >
                {{ project.company?.name }}
              </RouterLink>
              <span v-else>{{ project.company?.name }}</span>
            </td>
            <td class="px-4 py-3"><ProjectStatusBadge :status="project.status ?? 'draft'" /></td>
            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-500">{{ formatDate(project.created_at ?? '') }}</td>
            <td class="px-4 py-3">
              <button
                @click="$emit('delete-project', project.id)"
                class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <BasePagination
      v-if="pagination && pagination.last_page > 1"
      variant="company-applications"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      @change="$emit('page-change', $event)"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import ProjectStatusBadge from '@/components/projects/ProjectStatusBadge.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

interface Pagination {
  current_page: number
  last_page: number
  total: number
}

interface AdminProjectRow {
  id: number
  title?: string
  status?: string
  created_at?: string
  company?: {
    user_id?: number
    name?: string
  }
}

export default defineComponent({
  name: 'AdminProjectsPanel',
  components: { ProjectStatusBadge, BasePagination, ChevronDown },
  props: {
    projects: {
      type: Array as PropType<AdminProjectRow[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    pagination: {
      type: Object as PropType<Pagination | null>,
      default: null,
    },
  },
  emits: ['delete-project', 'page-change'],
  data() {
    return {
      searchQuery: '',
      statusFilter: '',
      statusDropdownOpen: false,
    }
  },
  computed: {
    visibleProjects(): AdminProjectRow[] {
      const query = String(this.searchQuery ?? '').trim().toLowerCase()

      return this.projects.filter((project) => {
        const title = String(project.title ?? '').toLowerCase()
        const company = String(project.company?.name ?? '').toLowerCase()
        const status = String(project.status ?? 'draft').toLowerCase()

        const matchesSearch = !query || title.includes(query) || company.includes(query)
        const matchesStatus = !this.statusFilter || status === this.statusFilter
        return matchesSearch && matchesStatus
      })
    },
    statusFilterLabel(): string {
      if (!this.statusFilter) return 'All statuses'
      return this.statusFilter.charAt(0).toUpperCase() + this.statusFilter.slice(1)
    },
  },
  mounted() {
    document.addEventListener('click', this.onDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick)
  },
  methods: {
    closeDropdowns() {
      this.statusDropdownOpen = false
    },
    toggleStatusDropdown() {
      const next = !this.statusDropdownOpen
      this.closeDropdowns()
      this.statusDropdownOpen = next
    },
    setStatusFilter(value: string) {
      this.statusFilter = value
      this.closeDropdowns()
    },
    onDocumentClick(event: Event) {
      const target = event.target as HTMLElement | null
      if (target?.closest('.admin-projects-dropdown')) return
      this.closeDropdowns()
    },
    formatDate(date: string): string {
      if (!date) return 'Unknown'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    },
  },
})
</script>

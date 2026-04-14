<template>
  <div class="space-y-5">
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
          <tr v-if="projects.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-slate-400 dark:text-slate-500">No projects found.</td>
          </tr>
          <tr v-for="project in projects" :key="project.id" class="hover:bg-[#f7f4fc] dark:hover:bg-slate-800/70">
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
  components: { ProjectStatusBadge, BasePagination },
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
  methods: {
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

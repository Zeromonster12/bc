<template>
  <div class="space-y-4">
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
      <div v-if="loading" class="space-y-2 p-4">
        <div v-for="n in 5" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left">Title</th>
            <th class="px-4 py-3 text-left">Company</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Created</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="projects.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-gray-400">No projects found.</td>
          </tr>
          <tr v-for="project in projects" :key="project.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ project.title }}</td>
            <td class="px-4 py-3 text-gray-600">{{ project.company?.name }}</td>
            <td class="px-4 py-3"><ProjectStatusBadge :status="project.status" /></td>
            <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(project.created_at) }}</td>
            <td class="px-4 py-3">
              <button
                @click="$emit('delete-project', project.id)"
                class="text-xs text-red-600 hover:text-red-800"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      v-if="pagination && pagination.last_page > 1"
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
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    },
  },
})
</script>

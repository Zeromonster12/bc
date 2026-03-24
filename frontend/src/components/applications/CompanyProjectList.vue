<template>
  <aside class="h-fit rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-900/90">
    <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-slate-100">My Projects</h2>

    <div v-if="projects.length === 0" class="py-4 text-sm text-gray-500 dark:text-slate-400">
      You do not have any projects yet.
    </div>

    <div v-else class="space-y-2">
      <button
        v-for="project in projects"
        :key="project.id"
        type="button"
        @click="$emit('select', project.id)"
        :class="[
          'w-full text-left border rounded-lg p-3 transition',
          selectedProjectId === project.id
            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/15'
            : 'border-gray-200 bg-white hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-500',
        ]"
      >
        <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ project.title }}</p>
        <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
          <span class="capitalize">{{ project.status }}</span>
          <span>{{ project.applications_count ?? 0 }} applicants</span>
        </div>
      </button>
    </div>
  </aside>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface CompanyProject {
  id: number
  title: string
  status: string
  applications_count?: number
}

export default defineComponent({
  name: 'CompanyProjectList',
  props: {
    projects: {
      type: Array as PropType<CompanyProject[]>,
      required: true,
    },
    selectedProjectId: {
      type: Number as PropType<number | null>,
      default: null,
    },
  },
  emits: ['select'],
})
</script>

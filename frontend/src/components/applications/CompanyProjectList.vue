<template>
  <aside class="bg-white border border-gray-200 rounded-xl p-4 h-fit">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">My Projects</h2>

    <div v-if="projects.length === 0" class="text-sm text-gray-500 py-4">
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
            ? 'border-indigo-500 bg-indigo-50'
            : 'border-gray-200 hover:border-gray-300 bg-white',
        ]"
      >
        <p class="text-sm font-medium text-gray-900 truncate">{{ project.title }}</p>
        <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
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

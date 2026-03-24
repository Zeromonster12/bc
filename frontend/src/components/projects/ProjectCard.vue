<template>
  <div
    class="cursor-pointer rounded-xl border border-gray-200 bg-white p-5 transition hover:shadow-md dark:border-slate-700/70 dark:bg-slate-900/90"
    @click="$emit('click', project)"
  >
    <div class="flex items-start justify-between gap-3 mb-3">
      <h3 class="line-clamp-2 flex-1 font-semibold text-gray-900 dark:text-slate-100">{{ project.title }}</h3>
      <ProjectStatusBadge :status="project.status ?? 'draft'" />
    </div>
    <p class="mb-4 line-clamp-3 text-sm text-gray-500 dark:text-slate-400">{{ project.description }}</p>
    <div class="flex flex-wrap gap-1.5 mb-4">
      <span
        v-for="tech in (project.tech_stack ?? []).slice(0, 4)"
        :key="tech"
        class="inline-flex items-center rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
      >
        {{ tech }}
      </span>
      <span
        v-if="(project.tech_stack ?? []).length > 4"
        class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-slate-300"
      >
        +{{ (project.tech_stack ?? []).length - 4 }} more
      </span>
    </div>
    <div class="flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
      <span>{{ project.company?.name }}</span>
      <span v-if="project.deadline">Due {{ formatDate(project.deadline) }}</span>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ProjectStatusBadge from './ProjectStatusBadge.vue'

interface ProjectCardItem {
  id: number
  title?: string
  status?: string
  description?: string
  tech_stack?: string[]
  deadline?: string | null
  company?: {
    name?: string
  }
}

export default defineComponent({
  name: 'ProjectCard',
  components: { ProjectStatusBadge },
  props: {
    project: {
      type: Object as PropType<ProjectCardItem>,
      required: true,
    },
  },
  emits: ['click'],
  methods: {
    formatDate(date: string): string {
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
  },
})
</script>

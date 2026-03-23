<template>
  <div
    class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition cursor-pointer"
    @click="$emit('click', project)"
  >
    <div class="flex items-start justify-between gap-3 mb-3">
      <h3 class="font-semibold text-gray-900 line-clamp-2 flex-1">{{ project.title }}</h3>
      <ProjectStatusBadge :status="project.status" />
    </div>
    <p class="text-sm text-gray-500 line-clamp-3 mb-4">{{ project.description }}</p>
    <div class="flex flex-wrap gap-1.5 mb-4">
      <span
        v-for="tech in (project.tech_stack ?? []).slice(0, 4)"
        :key="tech"
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700"
      >
        {{ tech }}
      </span>
      <span
        v-if="(project.tech_stack ?? []).length > 4"
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"
      >
        +{{ project.tech_stack.length - 4 }} more
      </span>
    </div>
    <div class="flex items-center justify-between text-xs text-gray-400">
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
  title: string
  status: string
  description: string
  tech_stack?: string[]
  deadline?: string
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

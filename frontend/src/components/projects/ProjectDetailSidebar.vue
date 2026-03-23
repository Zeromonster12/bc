<template>
  <div class="space-y-4">
    <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3 text-sm">
      <div class="flex justify-between">
        <span class="text-gray-500">Company</span>
        <span class="font-medium text-gray-900">{{ project.company?.name }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500">Deadline</span>
        <span class="font-medium text-gray-900">{{ formattedDeadline }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500">Max students</span>
        <span class="font-medium text-gray-900">{{ project.max_students }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500">Applications</span>
        <span class="font-medium text-gray-900">{{ project.applications_count ?? 0 }}</span>
      </div>
    </div>

    <div v-if="project.tech_stack?.length" class="bg-white border border-gray-200 rounded-xl p-5">
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Tech stack</h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="tech in project.tech_stack"
          :key="tech"
          class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-medium"
        >
          {{ tech }}
        </span>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface ProjectDetailSidebarItem {
  company?: {
    name?: string
  }
  max_students?: number
  applications_count?: number
  tech_stack?: string[]
}

export default defineComponent({
  name: 'ProjectDetailSidebar',
  props: {
    project: {
      type: Object as PropType<ProjectDetailSidebarItem>,
      required: true,
    },
    formattedDeadline: {
      type: String,
      default: '-',
    },
  },
})
</script>

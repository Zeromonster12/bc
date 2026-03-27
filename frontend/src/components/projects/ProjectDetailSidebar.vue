<template>
  <div class="space-y-4">
    <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-5 text-sm dark:border-slate-700/70 dark:bg-slate-900/90">
      <div class="flex justify-between">
        <span class="text-gray-500 dark:text-slate-400">Company</span>
        <RouterLink
          v-if="project.company?.user_id"
          :to="`/companies/${project.company.user_id}/profile`"
          class="font-medium text-gray-900 transition hover:text-indigo-600 dark:text-slate-100 dark:hover:text-indigo-300"
        >
          {{ project.company?.name }}
        </RouterLink>
        <span v-else class="font-medium text-gray-900 dark:text-slate-100">{{ project.company?.name }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500 dark:text-slate-400">Posted</span>
        <span class="font-medium text-gray-900 dark:text-slate-100">{{ formattedPostedAt }}</span>
      </div>
      <div class="flex justify-between gap-4">
        <span class="text-gray-500 dark:text-slate-400">Location</span>
        <span class="text-right font-medium text-gray-900 dark:text-slate-100">{{ project.location || 'Not specified' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500 dark:text-slate-400">Max students</span>
        <span class="font-medium text-gray-900 dark:text-slate-100">{{ project.max_students }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500 dark:text-slate-400">Applications</span>
        <span class="font-medium text-gray-900 dark:text-slate-100">{{ project.applications_count ?? 0 }}</span>
      </div>
    </div>

    <div
      v-if="project.tech_stack?.length"
      class="rounded-xl border border-gray-200 bg-white p-5 dark:border-slate-700/70 dark:bg-slate-900/90"
    >
      <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-slate-100">Tech stack</h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="tech in project.tech_stack"
          :key="tech"
          class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
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
    user_id?: number
    name?: string
  }
  location?: string | null
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
    formattedPostedAt: {
      type: String,
      default: '-',
    },
  },
})
</script>

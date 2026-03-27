<template>
  <aside class="flex h-full flex-col overflow-hidden rounded-3xl border border-[#ddd7ea] bg-[#efedf5] p-4 shadow-[0_10px_24px_rgba(77,55,197,0.08)] dark:border-slate-700/70 dark:bg-slate-900/95 dark:shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
    <div class="mb-3 border-b border-[#dfd9ee] pb-2 dark:border-slate-700/70">
      <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#77718f] dark:text-slate-400">Project filters</p>
      <h2 class="mt-1 text-sm font-semibold text-[#343047] dark:text-slate-100">My projects</h2>
    </div>

    <div v-if="projects.length === 0" class="rounded-2xl border border-dashed border-[#d8d2e8] bg-white/80 px-4 py-8 text-center text-sm text-[#6f6a84] dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-400">
      You do not have any projects yet.
    </div>

    <div v-else class="flex-1 space-y-2 overflow-y-auto pr-1">
      <button
        v-for="project in projects"
        :key="project.id"
        type="button"
        @click="$emit('select', project.id)"
        :class="[
          'w-full rounded-2xl border px-3.5 py-3 text-left transition',
          selectedProjectId === project.id
            ? 'border-[#5a42e5] bg-white text-[#2f2952] shadow-[0_8px_18px_rgba(77,55,197,0.18)] dark:border-indigo-400 dark:bg-slate-800 dark:text-slate-100'
            : 'border-[#ddd7ea] bg-white/90 text-[#3f3a56] hover:border-[#cfc7e4] hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-500',
        ]"
      >
        <p class="truncate text-sm font-semibold">{{ project.title }}</p>
        <div class="mt-2 flex items-center justify-between text-xs">
          <span
            class="rounded-full px-2 py-0.5 font-semibold capitalize"
            :class="
              project.status === 'open'
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                : project.status === 'closed'
                  ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
                  : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
            "
          >
            {{ project.status }}
          </span>
          <span class="font-medium text-[#68627f] dark:text-slate-400">{{ project.applications_count ?? 0 }} applicants</span>
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

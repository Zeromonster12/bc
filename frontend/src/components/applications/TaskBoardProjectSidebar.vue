<template>
  <aside
    class="flex h-full flex-col overflow-hidden rounded-3xl border border-[#ddd7ea] bg-[#efedf5] shadow-[0_10px_24px_rgba(77,55,197,0.08)] backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-[0_10px_24px_rgba(2,6,23,0.45)]"
  >
    <div class="border-b border-[#dfd9ee] px-4 py-3 dark:border-slate-700/70">
      <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#77718f] dark:text-slate-400">Task board</p>
      <h2 class="mt-1 text-sm font-semibold text-[#343047] dark:text-slate-100">
        {{ isStudent ? 'My accepted projects' : 'Company projects' }}
      </h2>
    </div>

    <div class="flex-1 space-y-1 overflow-auto p-2">
      <button
        v-for="project in projects"
        :key="project.id"
        type="button"
        :class="[
          'flex w-full items-center justify-between rounded-2xl border px-3 py-2.5 text-left transition',
          selectedProjectId === project.id
            ? 'border-[#5a42e5] bg-white text-[#2f2952] shadow-[0_8px_18px_rgba(77,55,197,0.18)] dark:border-indigo-400 dark:bg-slate-800 dark:text-slate-100'
            : 'border-[#ddd7ea] bg-white/90 text-[#3f3a56] hover:border-[#cfc7e4] hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500',
        ]"
        @click="$emit('select-project', project.id)"
      >
        <span class="truncate text-xs font-semibold">{{ project.title }}</span>
        <span
          :class="[
            'ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-semibold',
            selectedProjectId === project.id
              ? 'bg-[#ede8ff] text-[#4526c9] dark:bg-indigo-500/20 dark:text-indigo-300'
              : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
          ]"
        >
          {{ selectedProjectId === project.id ? selectedProjectTaskCount : '.' }}
        </span>
      </button>
    </div>
  </aside>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface TaskBoardProjectOption {
  id: number
  title: string
}

export default defineComponent({
  name: 'TaskBoardProjectSidebar',
  props: {
    isStudent: {
      type: Boolean,
      default: false,
    },
    projects: {
      type: Array as PropType<TaskBoardProjectOption[]>,
      default: () => [],
    },
    selectedProjectId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    selectedProjectTaskCount: {
      type: Number,
      default: 0,
    },
  },
  emits: ['select-project'],
})
</script>

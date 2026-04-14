<template>
  <aside
    class="flex h-full flex-col overflow-hidden rounded-3xl bg-white dark:bg-slate-900"
  >
    <div class="px-8 py-6">
      <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#66628b] dark:text-slate-400">Task board</p>
      <h2 class="mt-1 text-sm font-semibold text-[#2f2a4b] dark:text-slate-100">
        {{ isStudent ? 'My accepted projects' : 'Company projects' }}
      </h2>
    </div>

    <div class="flex-1 space-y-3 overflow-auto px-8 pb-8 pt-2">
      <button
        v-for="project in projects"
        :key="project.id"
        type="button"
        :class="[
          'flex w-full items-center justify-between rounded-2xl px-5 py-3.5 text-left transition',
          selectedProjectId === project.id
            ? 'bg-[#e8e3f2] text-[#2f2952] dark:bg-indigo-500/15 dark:text-slate-100'
            : 'bg-[#f1edf8] text-[#3f3a56] hover:bg-[#ebe6f5] dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-800',
        ]"
        @click="$emit('select-project', project.id)"
      >
        <span class="truncate text-sm font-semibold">{{ project.title }}</span>
        <span
          :class="[
            'ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold',
            selectedProjectId === project.id
              ? 'bg-[#5a42e5] text-white'
              : 'bg-[#d7d1ec] text-[#5c5480] dark:bg-slate-700 dark:text-slate-300',
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

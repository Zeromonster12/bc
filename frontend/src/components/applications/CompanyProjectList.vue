<template>
  <aside class="flex h-full flex-col overflow-hidden rounded-3xl bg-white dark:bg-slate-900">
    <div class="bg-white px-8 py-6 dark:bg-slate-900">
      <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#66628b] dark:text-slate-400">Project applicants</p>
      <h2 class="mt-1 text-sm font-semibold text-[#2f2a4b] dark:text-slate-100">My projects</h2>
      <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ filteredProjects.length }} projects</p>
      <input
        v-model.trim="searchQuery"
        type="text"
        placeholder="Search projects"
        class="mt-3 w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
      />
    </div>

    <div v-if="projects.length === 0" class="mx-8 rounded-2xl bg-[#f1edf8] px-4 py-8 text-center text-sm text-[#6f6a84] dark:bg-slate-800/70 dark:text-slate-400">
      You do not have any projects yet.
    </div>

    <div v-else-if="filteredProjects.length === 0" class="mx-8 rounded-2xl bg-[#f1edf8] px-4 py-8 text-center text-sm text-[#6f6a84] dark:bg-slate-800/70 dark:text-slate-400">
      No projects match your search.
    </div>

    <div v-else class="flex-1 space-y-3 overflow-y-auto px-8 pb-8 pt-2">
      <button
        v-for="project in filteredProjects"
        :key="project.id"
        type="button"
        @click="$emit('select', project.id)"
        :class="[
          'w-full rounded-2xl px-5 py-3.5 text-left transition',
          selectedProjectId === project.id
            ? 'bg-[#e8e3f2] text-[#2f2952] dark:bg-indigo-500/15 dark:text-slate-100'
            : 'bg-[#f1edf8] text-[#3f3a56] hover:bg-[#ebe6f5] dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-800',
        ]"
      >
        <div class="flex items-center justify-between gap-2">
          <p class="truncate text-sm font-semibold">{{ project.title }}</p>
          <span
            :class="[
              'ml-2 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold',
              selectedProjectId === project.id
                ? 'bg-[#5a42e5] text-white'
                : 'bg-[#d7d1ec] text-[#5c5480] dark:bg-slate-700 dark:text-slate-300',
            ]"
          >
            {{ project.applications_count ?? 0 }}
          </span>
        </div>
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
  data() {
    return {
      searchQuery: '',
    }
  },
  computed: {
    filteredProjects(): CompanyProject[] {
      const query = String(this.searchQuery ?? '').trim().toLowerCase()
      if (!query) return this.projects

      return this.projects.filter((project) => {
        const title = String(project.title ?? '').toLowerCase()
        const status = String(project.status ?? '').toLowerCase()
        return title.includes(query) || status.includes(query)
      })
    },
  },
})
</script>

<template>
  <div
    class="group relative flex h-full cursor-pointer flex-col overflow-hidden rounded-3xl bg-white p-4 transition duration-200 hover:bg-[#fcfbff] dark:bg-slate-900 dark:hover:bg-slate-800 sm:p-7"
    @click="$emit('click', project)"
  >
    <div class="mb-3 flex items-start justify-between gap-2 sm:gap-3">
      <h3 class="line-clamp-2 flex-1 text-base font-semibold leading-tight text-slate-900 dark:text-slate-100 sm:text-[1.05rem]">
        {{ project.title }}
      </h3>
      <ProjectStatusBadge :status="project.status ?? 'draft'" />
    </div>

    <p class="mb-3 line-clamp-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:mb-4">
      {{ project.description }}
    </p>

    <p
      v-if="project.location"
      class="mb-3 inline-flex max-w-full items-center gap-1.5 rounded-full bg-[#e8e3f2] px-2.5 py-1 text-xs font-medium text-[#4d466b] dark:bg-slate-800 dark:text-slate-300 sm:mb-4"
    >
      <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
      </svg>
      <span class="truncate">{{ project.location }}</span>
    </p>

    <div class="mb-4 flex flex-wrap gap-1.5 sm:mb-5 sm:gap-2">
      <span
        v-for="(tech, index) in (project.tech_stack ?? []).slice(0, 4)"
        :key="tech"
        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="techChipClass(index)"
      >
        {{ tech }}
      </span>
      <span
        v-if="(project.tech_stack ?? []).length > 4"
        class="inline-flex items-center rounded-full bg-[#ddd7f6] px-2.5 py-1 text-xs font-semibold text-[#4d466b] dark:bg-slate-700 dark:text-slate-300"
      >
        +{{ (project.tech_stack ?? []).length - 4 }} more
      </span>
    </div>

    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-3 text-xs sm:flex-nowrap sm:pt-4">
      <div class="flex min-w-0 items-center gap-2 text-slate-600 dark:text-slate-300">
        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e8e3f2] text-[10px] font-bold text-[#4d466b] dark:bg-slate-700 dark:text-slate-200">
          {{ companyInitials(project.company?.name) }}
        </span>
        <RouterLink
          v-if="companyProfilePath()"
          :to="companyProfilePath()"
          class="truncate font-medium transition hover:text-[#4f33d7]"
          @click.stop
        >
          {{ project.company?.name ?? 'Unknown company' }}
        </RouterLink>
        <span v-else class="truncate font-medium">{{ project.company?.name ?? 'Unknown company' }}</span>
      </div>

      <span class="inline-flex w-full items-center justify-end gap-1 font-medium text-slate-500 dark:text-slate-400 sm:w-auto">
        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-13 9h16a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1v12a1 1 0 001 1z" />
        </svg>
        {{ formatPostedDate(project.posted_at ?? project.created_at) }}
      </span>
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
  location?: string | null
  tech_stack?: string[]
  posted_at?: string | null
  created_at?: string | null
  company?: {
    user_id?: number
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
    companyProfilePath(): string {
      const companyUserId = Number(this.project.company?.user_id ?? 0)
      if (!Number.isFinite(companyUserId) || companyUserId <= 0) {
        return ''
      }

      return `/companies/${companyUserId}/profile`
    },
    companyInitials(name?: string): string {
      if (!name) return 'CO'
      return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part: string) => part[0])
        .join('')
        .toUpperCase()
    },
    techChipClass(index: number): string {
      const classes = [
        'bg-[#e8e3f2] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200',
        'bg-[#ddd7f6] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200',
        'bg-[#f1edf8] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200',
        'bg-[#e8e3f2] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200',
      ]
      return classes[index % classes.length] || 'bg-[#e8e3f2] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200'
    },
    formatPostedDate(date?: string | null): string {
      if (!date) return 'Posted recently'

      const parsed = new Date(date)
      if (Number.isNaN(parsed.getTime())) return 'Posted recently'

      return `Posted ${parsed.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })}`
    },
  },
})
</script>

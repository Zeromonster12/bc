<template>
  <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-900/90">
    <div class="flex items-start justify-between gap-2">
      <div class="flex items-start gap-3">
        <div
          class="h-10 w-10 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100 flex items-center justify-center dark:border-slate-600 dark:bg-slate-800"
        >
          <img
            v-if="applicantAvatarUrl"
            :src="applicantAvatarUrl"
            alt="Applicant avatar"
            class="h-full w-full object-cover"
          />
          <span v-else class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ applicantInitials }}</span>
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-900 dark:text-slate-100">
            {{ application.project?.title ?? 'Unknown Project' }}
          </h4>
          <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">
            {{ application.project?.company?.name }}
          </p>
          <p v-if="application.student?.name" class="mt-1 text-xs text-gray-600 dark:text-slate-300">
            Applicant:
            <a
              v-if="applicantProfileUrl"
              :href="applicantProfileUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="font-medium text-teal-700 hover:text-teal-800 hover:underline dark:text-teal-300 dark:hover:text-teal-200"
            >
              {{ application.student.name }}
            </a>
            <span v-else class="font-medium">
              {{ application.student.name }}
            </span>
            <span v-if="application.student?.email" class="text-gray-500 dark:text-slate-400"
              >({{ application.student.email }})</span
            >
          </p>
          <a
            v-if="application.student?.github_url"
            :href="application.student.github_url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-1 inline-flex text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
          >
            GitHub
            {{
              application.student?.github_username
                ? `@${application.student.github_username}`
                : 'profile'
            }}
          </a>
        </div>
      </div>
      <ApplicationStatusBadge :status="application.status ?? 'pending'" />
    </div>
    <p class="line-clamp-2 text-xs text-gray-600 dark:text-slate-300">{{ application.cover_letter ?? '' }}</p>

    <div
      v-if="application.status === 'accepted'"
      class="rounded-lg border border-emerald-100 bg-emerald-50/60 px-3 py-2 dark:border-emerald-500/30 dark:bg-emerald-500/10"
    >
      <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">
        Task summary for this application
      </p>
      <p class="mt-1 text-xs text-emerald-900/80 dark:text-emerald-200/90">
        Total: {{ taskStats.total }} | Todo: {{ taskStats.todo }} | In progress:
        {{ taskStats.inProgress }} | Complete: {{ taskStats.complete }}
      </p>
    </div>

    <div class="flex items-center justify-between">
      <span class="text-xs text-gray-400 dark:text-slate-500">Applied {{ formatDate(application.created_at) }}</span>
      <div class="flex gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ApplicationStatusBadge from './ApplicationStatusBadge.vue'
import { resolveAssetUrl } from '@/services/core/url'

interface ApplicationCardItem {
  id: number
  status?: string
  cover_letter?: string
  created_at?: string
  tasks?: Array<{
    id?: number
    title?: string
    status?: 'todo' | 'in_progress' | 'complete' | null
    created_at?: string
  }>
  student?: {
    name?: string
    email?: string
    avatar_url?: string | null
    github_connected?: boolean
    github_username?: string
    github_url?: string
  }
  project?: {
    title?: string
    company?: {
      name?: string
    }
  }
}

export default defineComponent({
  name: 'ApplicationCard',
  components: { ApplicationStatusBadge },
  props: {
    application: {
      type: Object as PropType<ApplicationCardItem>,
      required: true,
    },
  },
  computed: {
    applicantAvatarUrl(): string {
      return resolveAssetUrl(this.application.student?.avatar_url)
    },
    applicantInitials(): string {
      const name = this.application.student?.name ?? 'U'
      return name
        .split(' ')
        .map((p: string) => p[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
    applicantProfileUrl(): string {
      return this.application.student?.github_url ?? ''
    },
    taskStats(): { total: number; todo: number; inProgress: number; complete: number } {
      const tasks = this.application.tasks ?? []
      return {
        total: tasks.length,
        todo: tasks.filter((task) => task.status === 'todo').length,
        inProgress: tasks.filter((task) => task.status === 'in_progress').length,
        complete: tasks.filter((task) => task.status === 'complete').length,
      }
    },
  },
  methods: {
    formatDate(date?: string): string {
      if (!date) return 'Unknown'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
    formatDateTime(value?: string): string {
      if (!value) return 'Unknown'
      return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
  },
})
</script>

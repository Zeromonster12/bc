<template>
  <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-3">
    <div class="flex items-start justify-between gap-2">
      <div class="flex items-start gap-3">
        <div
          class="h-10 w-10 rounded-full bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0"
        >
          <img
            v-if="applicantAvatarUrl"
            :src="applicantAvatarUrl"
            alt="Applicant avatar"
            class="h-full w-full object-cover"
          />
          <span v-else class="text-xs font-semibold text-slate-600">{{ applicantInitials }}</span>
        </div>
        <div>
          <h4 class="font-medium text-gray-900 text-sm">
            {{ application.project?.title ?? 'Unknown Project' }}
          </h4>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ application.project?.company?.name }}
          </p>
          <p v-if="application.student?.name" class="text-xs text-gray-600 mt-1">
            Applicant: {{ application.student.name }}
            <span v-if="application.student?.email" class="text-gray-500"
              >({{ application.student.email }})</span
            >
          </p>
          <a
            v-if="application.student?.github_url"
            :href="application.student.github_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex mt-1 text-xs font-medium text-teal-700 hover:text-teal-800"
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
    <p class="text-xs text-gray-600 line-clamp-2">{{ application.cover_letter ?? '' }}</p>

    <div
      v-if="application.status === 'accepted'"
      class="rounded-lg border border-emerald-100 bg-emerald-50/60 px-3 py-2"
    >
      <p class="text-xs font-semibold text-emerald-800">Tasks summary</p>
      <p class="mt-1 text-xs text-emerald-900/80">
        Total: {{ taskStats.total }} | Todo: {{ taskStats.todo }} | In progress:
        {{ taskStats.inProgress }} | Complete: {{ taskStats.complete }}
      </p>
    </div>

    <div class="flex items-center justify-between">
      <span class="text-xs text-gray-400">Applied {{ formatDate(application.created_at) }}</span>
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
    id: number
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

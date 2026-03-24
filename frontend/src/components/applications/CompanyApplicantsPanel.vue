<template>
  <section class="space-y-3">
    <div
      v-if="!selectedProject"
      class="rounded-xl border border-gray-200 bg-white py-16 text-center text-gray-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
    >
      <p class="font-medium">Select a project to view applicants</p>
    </div>

    <template v-else>
      <div
        v-if="applications.length === 0"
        class="rounded-xl border border-gray-200 bg-white py-16 text-center text-gray-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
      >
        <p class="text-4xl mb-3">📝</p>
        <p class="font-medium">No applicants for this project</p>
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-700/70 dark:bg-slate-900/90"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left">Applicant</th>
              <th class="px-4 py-3 text-left">Applied</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Task summary</th>
              <th class="px-4 py-3 text-left">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
            <tr
              v-for="application in applications"
              :key="application.id"
              class="align-top hover:bg-gray-50 dark:hover:bg-slate-800/70"
            >
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900 dark:text-slate-100">
                  <a
                    v-if="application.student?.github_url"
                    :href="application.student.github_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-teal-700 hover:text-teal-800 hover:underline dark:text-teal-300 dark:hover:text-teal-200"
                  >
                    {{ application.student?.name || 'Unknown applicant' }}
                  </a>
                  <span v-else>{{ application.student?.name || 'Unknown applicant' }}</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ application.student?.email || 'No email' }}</p>
              </td>
              <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">
                {{ formatDate(application.created_at) }}
              </td>
              <td class="px-4 py-3">
                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs capitalize text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                  {{ application.status || 'pending' }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-300">
                <template v-if="application.status === 'accepted'">
                  <p>
                    Total: {{ taskStatsFor(application).total }} | Todo: {{ taskStatsFor(application).todo }} |
                    In progress: {{ taskStatsFor(application).inProgress }} | Complete:
                    {{ taskStatsFor(application).complete }}
                  </p>
                  <p class="mt-1 text-gray-500 dark:text-slate-400">Manage tasks in Task Board.</p>
                </template>
                <span v-else class="text-gray-400 dark:text-slate-500">-</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2" v-if="application.status === 'pending'">
                  <BaseButton
                    variant="primary"
                    size="sm"
                    :loading="updatingId === application.id && updatingStatus === 'accepted'"
                    @click="$emit('update-status', { id: application.id, status: 'accepted' })"
                  >
                    Accept
                  </BaseButton>
                  <BaseButton
                    variant="danger"
                    size="sm"
                    :loading="updatingId === application.id && updatingStatus === 'rejected'"
                    @click="$emit('update-status', { id: application.id, status: 'rejected' })"
                  >
                    Reject
                  </BaseButton>
                </div>
                <span v-else class="text-xs text-gray-400 dark:text-slate-500">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface CompanyProject {
  id: number
  title: string
}

interface ApplicationListItem {
  id: number
  cover_letter?: string
  created_at?: string
  status?: string
  tasks?: Array<{
    id?: number
    status?: 'todo' | 'in_progress' | 'complete' | null
  }>
  student?: {
    name?: string
    email?: string
    github_url?: string
  }
  [key: string]: unknown
}

export default defineComponent({
  name: 'CompanyApplicantsPanel',
  components: { BaseButton },
  props: {
    selectedProject: {
      type: Object as PropType<CompanyProject | null>,
      default: null,
    },
    applications: {
      type: Array as PropType<ApplicationListItem[]>,
      required: true,
    },
    updatingId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    updatingStatus: {
      type: String,
      default: '',
    },
  },
  emits: ['update-status'],
  methods: {
    formatDate(date?: string): string {
      if (!date) return 'Unknown'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
    taskStatsFor(application: ApplicationListItem): {
      total: number
      todo: number
      inProgress: number
      complete: number
    } {
      const tasks = application.tasks ?? []
      return {
        total: tasks.length,
        todo: tasks.filter((task) => task.status === 'todo').length,
        inProgress: tasks.filter((task) => task.status === 'in_progress').length,
        complete: tasks.filter((task) => task.status === 'complete').length,
      }
    },
  },
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Accepted Projects</h1>
        <p class="text-sm text-gray-600 mt-1">
          Track only projects where you were accepted and manage your current work status.
        </p>
      </div>

      <BaseAlert
        v-if="errorMessage"
        type="error"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />
      <BaseAlert
        v-if="successMessage"
        type="success"
        :message="successMessage"
        dismissible
        @dismiss="successMessage = ''"
      />

      <div v-if="loading" class="space-y-3">
        <div v-for="n in 4" :key="n" class="h-36 bg-gray-100 rounded-xl animate-pulse" />
      </div>

      <div
        v-else-if="acceptedApplications.length === 0"
        class="rounded-xl border border-gray-200 bg-white p-8 text-center"
      >
        <p class="text-sm text-gray-600">You do not have any accepted projects yet.</p>
      </div>

      <div v-else class="space-y-4">
        <article
          v-for="application in acceptedApplications"
          :key="application.id"
          class="rounded-xl border border-gray-200 bg-white p-5 space-y-4"
        >
          <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ application.project?.title ?? 'Project' }}
              </h2>
              <p class="text-sm text-gray-600">
                Company: {{ application.project?.company?.name ?? 'Unknown' }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                Project status: {{ projectStatusLabel(application.project?.status) }}
                <span v-if="application.project?.deadline">
                  | Deadline: {{ formatDate(application.project?.deadline) }}</span
                >
              </p>
            </div>
            <span
              class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"
            >
              Accepted
            </span>
          </div>

          <div class="grid gap-4 md:grid-cols-[220px_220px_1fr_auto] md:items-end">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">My progress</label>
              <select
                v-model="localStatus[application.id]"
                class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="not_started">Not started</option>
                <option value="in_progress">In progress</option>
                <option value="blocked">Blocked</option>
                <option value="completed">Completed</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Change title</label>
              <input
                v-model="localTitle[application.id]"
                type="text"
                maxlength="160"
                class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                placeholder="e.g. API integration finished"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
              <textarea
                v-model="localNote[application.id]"
                rows="2"
                maxlength="5000"
                class="w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                placeholder="What changed on this project?"
              />
            </div>

            <div class="md:self-end">
              <BaseButton
                variant="primary"
                :loading="savingId === application.id"
                @click="saveProgress(application.id)"
              >
                Submit update
              </BaseButton>
            </div>
          </div>

          <p v-if="application.student_project_status_updated_at" class="text-xs text-gray-500">
            Last updated: {{ formatDateTime(application.student_project_status_updated_at) }}
          </p>

          <section class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-slate-900">Timeline</h3>
              <span class="text-xs text-slate-500">Newest first</span>
            </div>

            <div v-if="timelineLoading[application.id]" class="mt-3 space-y-2">
              <div v-for="n in 2" :key="n" class="h-12 rounded bg-slate-200/70 animate-pulse" />
            </div>

            <ul v-else-if="(timelines[application.id] ?? []).length > 0" class="mt-3 space-y-3">
              <li
                v-for="entry in timelines[application.id]"
                :key="entry.id"
                class="relative rounded-md border border-slate-200 bg-white p-3"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-semibold text-slate-900">{{ entry.title }}</p>
                  <span
                    class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700"
                  >
                    {{ progressStatusLabel(entry.student_project_status) }}
                  </span>
                </div>
                <p v-if="entry.notes" class="mt-1 text-xs text-slate-600 whitespace-pre-line">
                  {{ entry.notes }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">
                  {{ formatDateTime(entry.created_at) }}
                </p>
              </li>
            </ul>

            <p v-else class="mt-3 text-xs text-slate-500">
              No updates yet. Submit your first project change above.
            </p>
          </section>
        </article>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useApplicationStore } from '@/stores/application'
import ApplicationService from '@/services/applications/ApplicationService'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

type StudentProgressStatus = 'not_started' | 'in_progress' | 'blocked' | 'completed'

interface AcceptedApplicationItem {
  id: number
  status: string
  student_project_status: StudentProgressStatus | null
  student_project_note: string | null
  student_project_status_updated_at: string | null
  progress_updates?: ProgressUpdateItem[]
  project?: {
    title?: string
    status?: string
    deadline?: string | null
    company?: {
      name?: string
    }
  }
}

interface ProgressUpdateItem {
  id: number
  title: string
  notes: string | null
  student_project_status: StudentProgressStatus | null
  created_at: string
}

export default defineComponent({
  name: 'AcceptedProjectsView',
  components: { AppLayout, BaseAlert, BaseButton },
  setup() {
    return {
      applicationStore: useApplicationStore(),
    }
  },
  data() {
    return {
      loading: true,
      savingId: null as number | null,
      errorMessage: '',
      successMessage: '',
      localStatus: {} as Record<number, StudentProgressStatus>,
      localTitle: {} as Record<number, string>,
      localNote: {} as Record<number, string>,
      timelineLoading: {} as Record<number, boolean>,
      timelines: {} as Record<number, ProgressUpdateItem[]>,
    }
  },
  computed: {
    acceptedApplications(): AcceptedApplicationItem[] {
      return (this.applicationStore.applications as AcceptedApplicationItem[]).filter(
        (item) => item.status === 'accepted',
      )
    },
  },
  async mounted() {
    await this.loadAcceptedProjects()
  },
  methods: {
    async loadAcceptedProjects() {
      this.loading = true
      this.errorMessage = ''

      try {
        await this.applicationStore.fetchApplications({ status: 'accepted', per_page: 100 })
        this.acceptedApplications.forEach((application) => {
          this.localStatus[application.id] = application.student_project_status ?? 'not_started'
          this.localTitle[application.id] = ''
          this.localNote[application.id] = application.student_project_note ?? ''
          this.timelines[application.id] = application.progress_updates ?? []
        })

        await Promise.all(
          this.acceptedApplications.map((application) => this.loadTimeline(application.id)),
        )
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage =
          typedError?.response?.data?.message ?? 'Failed to load accepted projects.'
      } finally {
        this.loading = false
      }
    },
    async loadTimeline(applicationId: number) {
      this.timelineLoading[applicationId] = true
      try {
        const response = await ApplicationService.listProgressUpdates(applicationId)
        this.timelines[applicationId] = response.data as ProgressUpdateItem[]
      } catch {
        this.timelines[applicationId] = this.timelines[applicationId] ?? []
      } finally {
        this.timelineLoading[applicationId] = false
      }
    },
    async saveProgress(applicationId: number) {
      const status = this.localStatus[applicationId] ?? 'not_started'
      const title = (this.localTitle[applicationId] ?? '').trim()
      const note = this.localNote[applicationId] ?? ''

      if (!title) {
        this.errorMessage = 'Please add a short change title before submitting update.'
        return
      }

      this.savingId = applicationId
      this.errorMessage = ''
      this.successMessage = ''

      try {
        const response = await ApplicationService.submitProgressUpdate(applicationId, {
          title,
          notes: note,
          student_project_status: status,
        })

        const updatedApplication = response.application as AcceptedApplicationItem
        const index = this.applicationStore.applications.findIndex(
          (app) => app.id === applicationId,
        )
        if (index !== -1) {
          this.applicationStore.applications[index] = updatedApplication as never
        }

        this.localStatus[applicationId] = (updatedApplication.student_project_status ??
          status) as StudentProgressStatus
        this.localNote[applicationId] = updatedApplication.student_project_note ?? ''
        this.localTitle[applicationId] = ''

        await this.loadTimeline(applicationId)

        this.successMessage = 'Project update submitted.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage =
          typedError?.response?.data?.message ?? 'Failed to submit project update.'
      } finally {
        this.savingId = null
      }
    },
    progressStatusLabel(status?: StudentProgressStatus | null): string {
      if (!status) return 'Unknown'
      if (status === 'not_started') return 'Not started'
      if (status === 'in_progress') return 'In progress'
      if (status === 'blocked') return 'Blocked'
      if (status === 'completed') return 'Completed'
      return status
    },
    formatDate(value?: string | null): string {
      if (!value) return 'Unknown'
      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) return 'Unknown'
      return parsed.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    },
    formatDateTime(value?: string | null): string {
      if (!value) return 'Unknown'
      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) return 'Unknown'
      return parsed.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    projectStatusLabel(status?: string): string {
      if (!status) return 'Unknown'
      if (status === 'open') return 'Open'
      if (status === 'closed') return 'Closed'
      if (status === 'draft') return 'Draft'
      return status
    },
  },
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Accepted Projects</h1>
        <p class="text-sm text-gray-600 mt-1">
          Track assigned tasks for projects where your application was accepted.
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

          <section class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-slate-900">My assigned tasks</h3>
              <span class="text-xs text-slate-500">Todo, in progress, complete</span>
            </div>

            <ul v-if="(tasksByApplication[application.id] ?? []).length > 0" class="mt-3 space-y-3">
              <li
                v-for="task in tasksByApplication[application.id]"
                :key="task.id"
                class="rounded-md border border-slate-200 bg-white p-3"
              >
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <p class="text-sm font-semibold text-slate-900">{{ task.title }}</p>
                  <span
                    class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700"
                  >
                    {{ priorityLabel(task.priority) }}
                  </span>
                </div>

                <p v-if="task.requirements" class="mt-1 text-xs text-slate-600 whitespace-pre-line">
                  {{ task.requirements }}
                </p>

                <div class="mt-3 grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-end">
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select
                      v-model="taskStatus[task.id]"
                      class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                      <option value="todo">Todo</option>
                      <option value="in_progress">In progress</option>
                      <option value="complete">Complete</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">My note</label>
                    <textarea
                      v-model="taskNote[task.id]"
                      rows="2"
                      maxlength="1000"
                      class="w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Optional note for company"
                    />
                  </div>

                  <div class="md:self-end">
                    <BaseButton
                      variant="primary"
                      :loading="savingTaskId === task.id"
                      @click="saveTask(application.id, task.id)"
                    >
                      Save
                    </BaseButton>
                  </div>
                </div>

                <p class="mt-2 text-[11px] text-slate-500">
                  Created {{ formatDateTime(task.created_at) }}
                  <span v-if="task.completed_at">
                    | Completed {{ formatDateTime(task.completed_at) }}</span
                  >
                </p>
              </li>
            </ul>

            <p v-else class="mt-3 text-xs text-slate-500">No tasks assigned yet.</p>
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

type TaskStatus = 'todo' | 'in_progress' | 'complete'
type TaskPriority = 'low' | 'medium' | 'high' | 'urgent'

interface ApplicationTaskItem {
  id: number
  title: string
  requirements: string | null
  priority: TaskPriority
  status: TaskStatus
  student_note: string | null
  created_at: string
  completed_at: string | null
}

interface AcceptedApplicationItem {
  id: number
  status: string
  tasks?: ApplicationTaskItem[]
  project?: {
    title?: string
    status?: string
    deadline?: string | null
    company?: {
      name?: string
    }
  }
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
      savingTaskId: null as number | null,
      errorMessage: '',
      successMessage: '',
      tasksByApplication: {} as Record<number, ApplicationTaskItem[]>,
      taskStatus: {} as Record<number, TaskStatus>,
      taskNote: {} as Record<number, string>,
    }
  },
  computed: {
    acceptedApplications(): AcceptedApplicationItem[] {
      return (this.applicationStore.applications as unknown as AcceptedApplicationItem[]).filter(
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
          const tasks = (application.tasks ?? []) as ApplicationTaskItem[]
          this.tasksByApplication[application.id] = tasks
          tasks.forEach((task) => {
            this.taskStatus[task.id] = task.status
            this.taskNote[task.id] = task.student_note ?? ''
          })
        })
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage =
          typedError?.response?.data?.message ?? 'Failed to load accepted projects.'
      } finally {
        this.loading = false
      }
    },
    async saveTask(applicationId: number, taskId: number) {
      const status = this.taskStatus[taskId] ?? 'todo'
      const studentNote = (this.taskNote[taskId] ?? '').trim()

      this.savingTaskId = taskId
      this.errorMessage = ''
      this.successMessage = ''

      try {
        const response = await ApplicationService.updateTask(applicationId, taskId, {
          status,
          student_note: studentNote,
        })

        const updatedTask = response.data as ApplicationTaskItem
        const tasks = this.tasksByApplication[applicationId] ?? []
        this.tasksByApplication[applicationId] = tasks.map((task) =>
          task.id === updatedTask.id ? updatedTask : task,
        )
        this.taskStatus[taskId] = updatedTask.status
        this.taskNote[taskId] = updatedTask.student_note ?? ''

        this.successMessage = 'Task updated.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to update task.'
      } finally {
        this.savingTaskId = null
      }
    },
    priorityLabel(priority: TaskPriority): string {
      if (priority === 'low') return 'Low'
      if (priority === 'medium') return 'Medium'
      if (priority === 'high') return 'High'
      if (priority === 'urgent') return 'Urgent'
      return priority
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

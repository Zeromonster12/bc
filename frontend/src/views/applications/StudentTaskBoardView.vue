<template>
  <AppLayout>
    <div class="h-full overflow-hidden space-y-3 lg:my-0 xl:ml-0">
      <div class="grid h-full min-h-0 items-stretch gap-2 xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="flex h-full flex-col overflow-hidden rounded-3xl border border-[#ddd7ea] bg-[#efedf5] shadow-[0_10px_24px_rgba(77,55,197,0.08)] backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
          <div class="border-b border-[#dfd9ee] px-4 py-3 dark:border-slate-700/70">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#77718f] dark:text-slate-400">Task board</p>
            <h2 class="mt-1 text-sm font-semibold text-[#343047] dark:text-slate-100">My accepted projects</h2>
          </div>

          <div v-if="projectOptions.length === 0" class="px-4 py-6 text-sm text-[#6f6a84] dark:text-slate-400">
            No accepted projects yet.
          </div>

          <div v-else class="flex-1 space-y-1 overflow-auto p-2">
            <button
              v-for="project in projectOptions"
              :key="project.id"
              type="button"
              :class="[
                'flex w-full items-center justify-between rounded-2xl border px-3 py-2.5 text-left transition',
                selectedProjectId === project.id
                  ? 'border-[#5a42e5] bg-white text-[#2f2952] shadow-[0_8px_18px_rgba(77,55,197,0.18)] dark:border-indigo-400 dark:bg-slate-800 dark:text-slate-100'
                  : 'border-[#ddd7ea] bg-white/90 text-[#3f3a56] hover:border-[#cfc7e4] hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500',
              ]"
              @click="selectProject(project.id)"
            >
              <span class="truncate text-xs font-semibold">{{ project.title }}</span>
              <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#ede8ff] px-1 text-[10px] font-semibold text-[#4526c9] dark:bg-indigo-500/20 dark:text-indigo-300">
                {{ project.taskCount }}
              </span>
            </button>
          </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-col gap-3 overflow-hidden">
          <BaseAlert v-if="errorMessage" type="error" :message="errorMessage" dismissible @dismiss="errorMessage = ''" />
          <BaseAlert v-if="successMessage" type="success" :message="successMessage" dismissible @dismiss="successMessage = ''" />

          <div v-if="loading" class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-36 animate-pulse rounded-3xl bg-slate-100 dark:bg-slate-800" />
          </div>

          <div v-else-if="!selectedProjectId" class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300">
            Select an accepted project from the sidebar.
          </div>

          <div v-else class="min-h-0 flex-1 overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-[0_10px_26px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900 dark:shadow-[0_10px_26px_rgba(2,6,23,0.5)]">
            <div class="h-full overflow-y-auto overflow-x-hidden rounded-3xl bg-white p-3 sm:p-4 lg:p-5 dark:bg-slate-900">
              <section
                v-for="(status, index) in boardStatuses"
                :key="status"
                :class="[
                  'overflow-visible transition',
                  index < boardStatuses.length - 1 ? 'border-b border-slate-200/60 dark:border-slate-700/60' : '',
                ]"
              >
                <div class="flex items-center gap-2 px-3 pb-2 pt-2.5">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ statusLabel(status) }}</h2>
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ ownTasksByStatus(status).length }}
                  </span>
                </div>

                <div v-if="ownTasksByStatus(status).length === 0" class="px-3 pb-4 text-xs text-slate-500 dark:text-slate-400">
                  No tasks in this status.
                </div>

                <div v-else class="space-y-2 px-3 pb-4">
                  <article
                    v-for="task in ownTasksByStatus(status)"
                    :key="task.id"
                    class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/70"
                  >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                      <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ task.title }}</p>
                      <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                        {{ priorityLabel(task.priority) }}
                      </span>
                    </div>

                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ task.folderName }}<span v-if="task.categoryName"> / {{ task.categoryName }}</span></p>

                    <div class="mt-3 grid gap-3 sm:grid-cols-[220px_auto] sm:items-end">
                      <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-slate-300">Status</label>
                        <select
                          v-model="taskStatusDraft[task.id]"
                          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        >
                          <option value="todo">Todo</option>
                          <option value="in_progress">In progress</option>
                          <option value="complete">Complete</option>
                        </select>
                      </div>

                      <div>
                        <BaseButton
                          variant="primary"
                          :loading="savingTaskId === task.id"
                          @click="saveTask(task)"
                        >
                          Save status
                        </BaseButton>
                      </div>
                    </div>
                  </article>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ApplicationService, { type ApplicationTaskPriority, type ApplicationTaskStatus, type ProjectTaskBoardFolder } from '@/services/applications/ApplicationService'
import { useAuthStore } from '@/stores/auth'

interface AcceptedApplicationItem {
  id: number
  project?: {
    id?: number
    title?: string
  }
}

interface ProjectOption {
  id: number
  title: string
  applicationId: number
  taskCount: number
}

interface BoardTaskViewModel {
  id: number
  application_id: number
  title: string
  priority: ApplicationTaskPriority
  status: ApplicationTaskStatus
  assigneeId: number | null
  folderName: string
  categoryName: string
}

export default defineComponent({
  name: 'StudentTaskBoardView',
  components: { AppLayout, BaseAlert, BaseButton },
  setup() {
    return {
      auth: useAuthStore(),
    }
  },
  data() {
    return {
      loading: true,
      savingTaskId: null as number | null,
      errorMessage: '',
      successMessage: '',
      selectedProjectId: null as number | null,
      projectOptions: [] as ProjectOption[],
      sections: {
        todo: [],
        in_progress: [],
        complete: [],
      } as Record<ApplicationTaskStatus, ProjectTaskBoardFolder[]>,
      taskStatusDraft: {} as Record<number, ApplicationTaskStatus>,
      boardStatuses: ['todo', 'in_progress', 'complete'] as ApplicationTaskStatus[],
    }
  },
  async mounted() {
    await this.loadAcceptedProjectsAndBoard()
  },
  methods: {
    async loadAcceptedProjectsAndBoard() {
      this.loading = true
      this.errorMessage = ''

      try {
        const response = await ApplicationService.getAll({ status: 'accepted', per_page: 100 })
        const rawItems = Array.isArray(response?.data) ? response.data : []

        const byProject = new Map<number, ProjectOption>()
        rawItems.forEach((item: AcceptedApplicationItem) => {
          const projectId = Number(item?.project?.id ?? 0)
          if (!projectId) return

          const existing = byProject.get(projectId)
          if (existing) return

          byProject.set(projectId, {
            id: projectId,
            title: String(item?.project?.title ?? `Project #${projectId}`).trim(),
            applicationId: Number(item?.id ?? 0),
            taskCount: 0,
          })
        })

        this.projectOptions = Array.from(byProject.values())

        if (this.projectOptions.length > 0) {
          this.selectedProjectId = this.projectOptions[0]!.id
          await this.loadBoard(this.selectedProjectId)
        }
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to load accepted projects.'
      } finally {
        this.loading = false
      }
    },
    async selectProject(projectId: number) {
      if (this.selectedProjectId === projectId) return
      this.selectedProjectId = projectId
      await this.loadBoard(projectId)
    },
    async loadBoard(projectId: number) {
      this.loading = true
      this.errorMessage = ''

      try {
        const board = await ApplicationService.getProjectTaskBoard(projectId)
        this.sections = board.data.sections
        this.syncTaskDrafts()
        this.updateProjectTaskCounts()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to load task board.'
      } finally {
        this.loading = false
      }
    },
    syncTaskDrafts() {
      const nextDrafts: Record<number, ApplicationTaskStatus> = {}
      this.boardStatuses.forEach((status) => {
        this.ownTasksByStatus(status).forEach((task) => {
          nextDrafts[task.id] = task.status
        })
      })
      this.taskStatusDraft = nextDrafts
    },
    ownTasksByStatus(status: ApplicationTaskStatus): BoardTaskViewModel[] {
      const userId = Number(this.auth.user?.id ?? 0)
      const result: BoardTaskViewModel[] = []
      const folders = this.sections[status] ?? []

      folders.forEach((folder) => {
        const folderName = folder.is_virtual ? 'No folder' : folder.name

        ;(folder.uncategorized_tasks ?? []).forEach((task) => {
          if (Number(task?.assignee?.id ?? 0) !== userId) return
          result.push({
            id: task.id,
            application_id: task.application_id,
            title: task.title,
            priority: task.priority,
            status: task.status,
            assigneeId: task.assignee?.id ?? null,
            folderName,
            categoryName: '',
          })
        })

        ;(folder.categories ?? []).forEach((category) => {
          ;(category.tasks ?? []).forEach((task) => {
            if (Number(task?.assignee?.id ?? 0) !== userId) return
            result.push({
              id: task.id,
              application_id: task.application_id,
              title: task.title,
              priority: task.priority,
              status: task.status,
              assigneeId: task.assignee?.id ?? null,
              folderName,
              categoryName: category.name,
            })
          })
        })
      })

      return result
    },
    updateProjectTaskCounts() {
      const countForCurrent = this.boardStatuses
        .reduce((sum, status) => sum + this.ownTasksByStatus(status).length, 0)

      this.projectOptions = this.projectOptions.map((option) => {
        if (option.id !== this.selectedProjectId) return option
        return {
          ...option,
          taskCount: countForCurrent,
        }
      })
    },
    async saveTask(task: BoardTaskViewModel) {
      const nextStatus = this.taskStatusDraft[task.id] ?? task.status
      this.savingTaskId = task.id
      this.errorMessage = ''
      this.successMessage = ''

      try {
        await ApplicationService.updateTask(task.application_id, task.id, {
          status: nextStatus,
        })
        this.successMessage = 'Task status updated.'
        if (this.selectedProjectId) {
          await this.loadBoard(this.selectedProjectId)
        }
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to update task status.'
      } finally {
        this.savingTaskId = null
      }
    },
    statusLabel(status: ApplicationTaskStatus): string {
      if (status === 'todo') return 'TO DO'
      if (status === 'in_progress') return 'IN PROGRESS'
      return 'COMPLETED'
    },
    priorityLabel(priority: ApplicationTaskPriority): string {
      if (priority === 'low') return 'Low'
      if (priority === 'medium') return 'Medium'
      if (priority === 'high') return 'High'
      if (priority === 'urgent') return 'Urgent'
      return priority
    },
  },
})
</script>

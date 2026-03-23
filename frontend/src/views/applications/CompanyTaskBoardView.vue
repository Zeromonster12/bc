<template>
  <AppLayout>
    <div class="space-y-5">
      <div
        class="rounded-2xl border border-slate-200 bg-linear-to-b from-white via-slate-50 to-slate-100/80 p-4 shadow-sm"
      >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
              Company Workspace
            </p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Project Task Board</h1>
            <p class="mt-1 text-sm text-slate-600">
              Simple tree view like file explorer: folder, category, task.
            </p>
          </div>

          <div class="grid w-full gap-2 sm:grid-cols-[minmax(240px,1fr)_auto_auto] lg:w-auto">
            <div class="relative">
              <LayoutGrid
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
              <select
                v-model="selectedProjectId"
                class="block w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                @change="onProjectChange"
              >
                <option :value="null">Select project</option>
                <option v-for="project in companyProjects" :key="project.id" :value="project.id">
                  {{ project.title }}
                </option>
              </select>
            </div>

            <BaseButton
              variant="secondary"
              :disabled="!selectedProjectId"
              class="rounded-xl!"
              @click="openCreateFolder = true"
            >
              <FolderPlus class="h-4 w-4" />
              New folder
            </BaseButton>

            <BaseButton
              variant="secondary"
              :disabled="!selectedProjectId || !selectedFolderId"
              class="rounded-xl!"
              @click="openCreateCategory = true"
            >
              <Plus class="h-4 w-4" />
              New subfolder
            </BaseButton>
          </div>
        </div>

        <div v-if="selectedProjectId" class="mt-4 grid grid-cols-3 gap-2 sm:max-w-lg">
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">To Do</p>
            <p class="text-lg font-semibold text-rose-900">{{ statusCount('todo') }}</p>
          </div>
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">
              In Progress
            </p>
            <p class="text-lg font-semibold text-amber-900">{{ statusCount('in_progress') }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">
              Completed
            </p>
            <p class="text-lg font-semibold text-emerald-900">{{ statusCount('complete') }}</p>
          </div>
        </div>
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
        <div v-for="n in 3" :key="n" class="h-36 animate-pulse rounded-2xl bg-slate-100" />
      </div>

      <div
        v-else-if="!selectedProjectId"
        class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600"
      >
        Select a project to open the board.
      </div>

      <div v-else class="space-y-3">
        <section
          v-for="status in boardStatuses"
          :key="status"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div class="flex items-center gap-2">
              <component :is="statusIcon(status)" :class="statusIconClass(status)" />
              <h2 class="text-sm font-semibold text-slate-900">{{ statusLabel(status) }}</h2>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
              >
                {{ statusCount(status) }}
              </span>
              <button
                class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                @click="openCreateFolder = true"
              >
                <FolderPlus class="h-3 w-3" />
                New folder
              </button>
            </div>
          </div>

          <div
            class="mx-2 mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-500"
            @dragover.prevent
            @drop.prevent="onStatusDrop(status)"
          >
            Drop task here to move it to {{ statusLabel(status).toLowerCase() }}.
          </div>

          <div
            v-if="flattenedFoldersForStatus(status).length === 0"
            class="px-4 py-4 text-sm text-slate-500"
          >
            No tasks in this section.
          </div>

          <div v-else class="px-2 py-2">
            <div
              v-for="entry in flattenedFoldersForStatus(status)"
              :key="`folder-${status}-${entry.folder.id}`"
              class="rounded-xl border border-transparent hover:border-slate-200"
              @dragover.prevent
              @drop.prevent="onFolderDrop(status, entry.folder.id)"
            >
              <button
                class="group flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left transition hover:bg-slate-50"
                :draggable="true"
                :style="{ paddingLeft: `${entry.depth * 18 + 10}px` }"
                @dragstart="onFolderDragStart(status, entry.folder.id, $event)"
                @click="toggleFolder(status, entry.folder.id)"
              >
                <span class="flex items-center gap-2">
                  <ChevronRight
                    v-if="!isFolderOpen(status, entry.folder.id)"
                    class="h-4 w-4 text-slate-500"
                  />
                  <ChevronDown v-else class="h-4 w-4 text-slate-500" />
                  <Folder
                    v-if="!isFolderOpen(status, entry.folder.id)"
                    class="h-4 w-4 text-amber-500"
                  />
                  <FolderOpen v-else class="h-4 w-4 text-amber-500" />
                  <span class="text-sm font-medium text-slate-800">{{ entry.folder.name }}</span>
                </span>
                <span class="flex items-center gap-2">
                  <span class="text-xs text-slate-500">{{ folderTaskCount(entry.folder) }}</span>
                  <button
                    type="button"
                    class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-white"
                    @click.stop="renameFolder(entry.folder.id, entry.folder.name)"
                  >
                    Rename
                  </button>
                  <button
                    type="button"
                    class="rounded border border-rose-200 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 hover:bg-rose-50"
                    @click.stop="deleteFolder(entry.folder.id, entry.folder.name)"
                  >
                    Delete
                  </button>
                </span>
              </button>

              <div
                v-if="isFolderOpen(status, entry.folder.id)"
                class="border-l border-slate-200 pl-3"
                :style="{ marginLeft: `${entry.depth * 18 + 30}px` }"
              >
                <div
                  class="mb-2 space-y-1 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-2"
                  @dragover.prevent
                  @drop.prevent="onTaskDrop(status, entry.folder.id, null)"
                >
                  <p class="px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Tasks in folder
                  </p>
                  <div
                    v-for="task in entry.folder.uncategorized_tasks"
                    :key="`task-folder-${task.id}`"
                    class="cursor-grab rounded-lg border border-slate-200 bg-white px-3 py-2 active:cursor-grabbing"
                    :draggable="true"
                    @dragstart="onTaskDragStart(task, status, entry.folder.id, null, $event)"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-900">{{ task.title }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                          <UserRound class="h-3.5 w-3.5" />
                          {{ task.assignee.name || task.assignee.email || 'Unknown assignee' }}
                        </p>
                      </div>
                      <div class="flex items-center gap-2">
                        <span :class="priorityPillClass(task.priority)">
                          {{ priorityLabel(task.priority) }}
                        </span>
                        <button
                          type="button"
                          class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-white"
                          @click.stop="renameTask(task.application_id, task.id, task.title)"
                        >
                          Rename
                        </button>
                        <button
                          type="button"
                          class="rounded border border-rose-200 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 hover:bg-rose-50"
                          @click.stop="deleteTask(task.application_id, task.id, task.title)"
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-for="category in entry.folder.categories"
                  :key="`cat-${status}-${category.id}`"
                  class="py-1"
                  @dragover.prevent
                  @drop.prevent="onTaskDrop(status, entry.folder.id, category.id)"
                >
                  <button
                    class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left transition hover:bg-slate-50"
                    @click="toggleCategory(status, category.id)"
                  >
                    <span class="flex items-center gap-2">
                      <ChevronRight
                        v-if="!isCategoryOpen(status, category.id)"
                        class="h-3.5 w-3.5 text-slate-500"
                      />
                      <ChevronDown v-else class="h-3.5 w-3.5 text-slate-500" />
                      <Folder class="h-3.5 w-3.5 text-sky-500" />
                      <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                        {{ category.name }}
                      </span>
                    </span>
                    <span class="flex items-center gap-2">
                      <span class="text-xs text-slate-500">{{ category.tasks.length }}</span>
                      <button
                        type="button"
                        class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-white"
                        @click.stop="renameCategory(entry.folder.id, category.id, category.name)"
                      >
                        Rename
                      </button>
                      <button
                        type="button"
                        class="rounded border border-rose-200 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 hover:bg-rose-50"
                        @click.stop="deleteCategory(entry.folder.id, category.id, category.name)"
                      >
                        Delete
                      </button>
                    </span>
                  </button>

                  <div
                    v-if="isCategoryOpen(status, category.id)"
                    class="ml-5 mt-1 space-y-1 border-l border-slate-200 pl-3"
                    @dragover.prevent
                    @drop.prevent="onTaskDrop(status, entry.folder.id, category.id)"
                  >
                    <div
                      v-for="task in category.tasks"
                      :key="`task-${task.id}`"
                      class="cursor-grab rounded-lg border border-slate-200 bg-white px-3 py-2 active:cursor-grabbing"
                      :draggable="true"
                      @dragstart="
                        onTaskDragStart(task, status, entry.folder.id, category.id, $event)
                      "
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <p class="truncate text-sm font-medium text-slate-900">
                            {{ task.title }}
                          </p>
                          <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                            <UserRound class="h-3.5 w-3.5" />
                            {{ task.assignee.name || task.assignee.email || 'Unknown assignee' }}
                          </p>
                        </div>
                        <div class="flex items-center gap-2">
                          <span :class="priorityPillClass(task.priority)">
                            {{ priorityLabel(task.priority) }}
                          </span>
                          <button
                            type="button"
                            class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-white"
                            @click.stop="renameTask(task.application_id, task.id, task.title)"
                          >
                            Rename
                          </button>
                          <button
                            type="button"
                            class="rounded border border-rose-200 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 hover:bg-rose-50"
                            @click.stop="deleteTask(task.application_id, task.id, task.title)"
                          >
                            Delete
                          </button>
                        </div>
                      </div>
                    </div>

                    <p v-if="category.tasks.length === 0" class="px-2 py-1 text-xs text-slate-400">
                      Empty category
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div
        v-if="openCreateFolder"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
          <h3 class="text-sm font-semibold text-slate-900">Create folder</h3>
          <input
            v-model="newFolderName"
            type="text"
            maxlength="120"
            class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
            placeholder="Folder name"
          />
          <div class="mt-4 flex justify-end gap-2">
            <BaseButton variant="secondary" class="rounded-xl!" @click="openCreateFolder = false">
              Cancel
            </BaseButton>
            <BaseButton
              variant="primary"
              class="rounded-xl!"
              :loading="submitting"
              @click="createFolder"
            >
              Create
            </BaseButton>
          </div>
        </div>
      </div>

      <div
        v-if="openCreateCategory"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
          <h3 class="text-sm font-semibold text-slate-900">Create subfolder</h3>

          <select
            v-model="selectedFolderId"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          >
            <option :value="null">Select folder</option>
            <option v-for="folder in folderOptions" :key="folder.id" :value="folder.id">
              {{ folder.name }}
            </option>
          </select>

          <input
            v-model="newCategoryName"
            type="text"
            maxlength="120"
            class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
            placeholder="Category name"
          />

          <div class="mt-4 flex justify-end gap-2">
            <BaseButton variant="secondary" class="rounded-xl!" @click="openCreateCategory = false">
              Cancel
            </BaseButton>
            <BaseButton
              variant="primary"
              class="rounded-xl!"
              :loading="submitting"
              @click="createCategory"
            >
              Create
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import {
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Circle,
  CircleDot,
  Folder,
  FolderOpen,
  FolderPlus,
  LayoutGrid,
  Plus,
  UserRound,
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useProjectStore } from '@/stores/project'
import { useAuthStore } from '@/stores/auth'
import ApplicationService, {
  type ApplicationTaskStatus,
  type ProjectTaskBoardFolder,
  type ProjectTaskBoardTask,
} from '@/services/applications/ApplicationService'

type SectionMap = Record<ApplicationTaskStatus, ProjectTaskBoardFolder[]>

interface SimpleCategory {
  id: number
  name: string
}

interface FolderOption {
  id: number
  name: string
  parent_folder_id?: number | null
  categories: SimpleCategory[]
}

interface FlatFolderEntry {
  folder: ProjectTaskBoardFolder
  depth: number
}

interface TaskDragPayload {
  taskId: number
  applicationId: number
  task: ProjectTaskBoardTask
  fromStatus: ApplicationTaskStatus
  fromFolderId: number
  fromCategoryId: number | null
}

interface FolderDragPayload {
  folderId: number
  fromStatus: ApplicationTaskStatus
}

export default defineComponent({
  name: 'CompanyTaskBoardView',
  components: {
    AppLayout,
    BaseAlert,
    BaseButton,
    LayoutGrid,
    FolderPlus,
    Plus,
    Folder,
    FolderOpen,
    ChevronRight,
    ChevronDown,
    UserRound,
    Circle,
    CircleDot,
    CheckCircle2,
  },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
    }
  },
  data() {
    return {
      loading: true,
      submitting: false,
      selectedProjectId: null as number | null,
      errorMessage: '',
      successMessage: '',
      sections: {
        todo: [],
        in_progress: [],
        complete: [],
      } as SectionMap,
      openFolders: {} as Record<string, boolean>,
      openCategories: {} as Record<string, boolean>,
      openCreateFolder: false,
      openCreateCategory: false,
      newFolderName: '',
      newCategoryName: '',
      selectedFolderId: null as number | null,
      folderOptions: [] as FolderOption[],
      draggedTask: null as TaskDragPayload | null,
      draggedFolder: null as FolderDragPayload | null,
      boardStatuses: ['todo', 'in_progress', 'complete'] as ApplicationTaskStatus[],
    }
  },
  computed: {
    companyProjects() {
      return this.projectStore.projects
    },
  },
  async mounted() {
    this.loading = true
    try {
      await this.projectStore.fetchProjects({
        company_id: Number(this.auth.user?.id),
        per_page: 100,
      })
      const firstProject = this.companyProjects[0]
      if (firstProject) {
        this.selectedProjectId = firstProject.id
        await this.loadBoard(firstProject.id)
      }
    } catch (error: unknown) {
      const typedError = error as { response?: { data?: { message?: string } } }
      this.errorMessage = typedError?.response?.data?.message ?? 'Failed to load projects.'
    } finally {
      this.loading = false
    }
  },
  methods: {
    async onProjectChange() {
      if (!this.selectedProjectId) {
        this.sections = { todo: [], in_progress: [], complete: [] }
        this.folderOptions = []
        return
      }
      await this.loadBoard(this.selectedProjectId)
    },
    async loadBoard(projectId: number) {
      this.loading = true
      this.errorMessage = ''
      try {
        const board = await ApplicationService.getProjectTaskBoard(projectId)
        this.sections = board.data.sections
        this.hydrateOpenStates()
        await this.loadFolders(projectId)
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to load task board.'
      } finally {
        this.loading = false
      }
    },
    async loadFolders(projectId: number) {
      const response = await ApplicationService.listTaskFolders(projectId)
      this.folderOptions = (
        response.data as Array<{
          id: number
          name: string
          parent_folder_id?: number | null
          categories?: Array<{ id: number; name: string }>
        }>
      ).map((folder) => ({
        id: folder.id,
        name: folder.name,
        parent_folder_id: folder.parent_folder_id ?? null,
        categories: (folder.categories ?? []).map((category) => ({
          id: category.id,
          name: category.name,
        })),
      }))

      if (!this.selectedFolderId && this.folderOptions.length > 0) {
        this.selectedFolderId = this.folderOptions[0]!.id
      }
    },
    hydrateOpenStates() {
      const nextFolders: Record<string, boolean> = {}
      const nextCategories: Record<string, boolean> = {}
      this.boardStatuses.forEach((status) => {
        this.sections[status].forEach((folder) => {
          nextFolders[`${status}:${folder.id}`] = true
          folder.categories.forEach((category) => {
            nextCategories[`${status}:${category.id}`] = true
          })
        })
      })
      this.openFolders = nextFolders
      this.openCategories = nextCategories
    },
    toggleFolder(status: ApplicationTaskStatus, folderId: number) {
      const key = `${status}:${folderId}`
      this.openFolders[key] = !this.openFolders[key]
    },
    toggleCategory(status: ApplicationTaskStatus, categoryId: number) {
      const key = `${status}:${categoryId}`
      this.openCategories[key] = !this.openCategories[key]
    },
    isFolderOpen(status: ApplicationTaskStatus, folderId: number): boolean {
      return this.openFolders[`${status}:${folderId}`] ?? false
    },
    isCategoryOpen(status: ApplicationTaskStatus, categoryId: number): boolean {
      return this.openCategories[`${status}:${categoryId}`] ?? false
    },
    folderTaskCount(folder: ProjectTaskBoardFolder): number {
      const directTasks = folder.uncategorized_tasks?.length ?? 0
      return (
        directTasks + folder.categories.reduce((sum, category) => sum + category.tasks.length, 0)
      )
    },
    flattenedFoldersForStatus(status: ApplicationTaskStatus): FlatFolderEntry[] {
      const folders = this.sections[status]
      const byParent = new Map<number | null, ProjectTaskBoardFolder[]>()

      folders.forEach((folder) => {
        const key = folder.parent_folder_id ?? null
        const bucket = byParent.get(key)
        if (bucket) {
          bucket.push(folder)
        } else {
          byParent.set(key, [folder])
        }
      })

      byParent.forEach((bucket) => {
        bucket.sort((a, b) => {
          if (a.position === b.position) return a.id - b.id
          return a.position - b.position
        })
      })

      const entries: FlatFolderEntry[] = []
      const visited = new Set<number>()
      const walk = (parentId: number | null, depth: number) => {
        const children = byParent.get(parentId) ?? []
        children.forEach((folder) => {
          if (visited.has(folder.id)) return
          visited.add(folder.id)
          entries.push({ folder, depth })
          if (this.isFolderOpen(status, folder.id)) {
            walk(folder.id, depth + 1)
          }
        })
      }

      walk(null, 0)

      if (entries.length < folders.length) {
        folders.forEach((folder) => {
          if (!visited.has(folder.id)) {
            entries.push({ folder, depth: 0 })
          }
        })
      }

      return entries
    },
    statusCount(status: ApplicationTaskStatus): number {
      return this.sections[status].reduce((sum, folder) => sum + this.folderTaskCount(folder), 0)
    },
    statusLabel(status: ApplicationTaskStatus): string {
      if (status === 'todo') return 'TO DO'
      if (status === 'in_progress') return 'IN PROGRESS'
      return 'COMPLETED'
    },
    statusIcon(status: ApplicationTaskStatus) {
      if (status === 'todo') return 'Circle'
      if (status === 'in_progress') return 'CircleDot'
      return 'CheckCircle2'
    },
    statusIconClass(status: ApplicationTaskStatus): string {
      if (status === 'todo') return 'h-4 w-4 text-rose-500'
      if (status === 'in_progress') return 'h-4 w-4 text-amber-500'
      return 'h-4 w-4 text-emerald-600'
    },
    priorityLabel(priority: string): string {
      if (priority === 'low') return 'Low'
      if (priority === 'medium') return 'Medium'
      if (priority === 'high') return 'High'
      if (priority === 'urgent') return 'Urgent'
      return priority
    },
    priorityPillClass(priority: string): string {
      if (priority === 'urgent') {
        return 'inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700'
      }
      if (priority === 'high') {
        return 'inline-flex items-center rounded-full bg-orange-100 px-2 py-1 text-[11px] font-semibold text-orange-700'
      }
      if (priority === 'medium') {
        return 'inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700'
      }
      return 'inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600'
    },
    onFolderDragStart(status: ApplicationTaskStatus, folderId: number, event: DragEvent) {
      this.draggedFolder = { folderId, fromStatus: status }
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', `folder:${folderId}`)
      }
    },
    async onFolderDrop(status: ApplicationTaskStatus, targetFolderId: number) {
      if (!this.selectedProjectId) return

      if (this.draggedTask) {
        await this.moveTaskToTarget(this.draggedTask, status, targetFolderId, null)
        this.draggedTask = null
        return
      }

      if (!this.draggedFolder) return

      const sourceFolderId = this.draggedFolder.folderId
      this.draggedFolder = null

      if (sourceFolderId === targetFolderId) return

      if (this.wouldCreateLocalFolderCycle(status, sourceFolderId, targetFolderId)) {
        this.errorMessage = 'This move would create a folder cycle.'
        return
      }

      const targetPosition = this.nextFolderPosition(status, targetFolderId)

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.updateTaskFolder(
          this.selectedProjectId as number,
          sourceFolderId,
          {
            parent_folder_id: targetFolderId,
            position: targetPosition,
          },
        )

        this.updateFolderParentLocally(sourceFolderId, targetFolderId, targetPosition)
        this.openFolders[`${status}:${targetFolderId}`] = true
        this.successMessage = 'Folder moved as subfolder.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to move folder.'
      } finally {
        this.submitting = false
      }
    },
    onTaskDragStart(
      task: ProjectTaskBoardTask,
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
      event: DragEvent,
    ) {
      this.draggedTask = {
        taskId: task.id,
        applicationId: task.application_id,
        task,
        fromStatus: status,
        fromFolderId: folderId,
        fromCategoryId: categoryId,
      }
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', `task:${task.id}`)
      }
    },
    async onTaskDrop(status: ApplicationTaskStatus, folderId: number, categoryId: number | null) {
      if (!this.draggedTask) return
      await this.moveTaskToTarget(this.draggedTask, status, folderId, categoryId)
      this.draggedTask = null
    },
    async onStatusDrop(status: ApplicationTaskStatus) {
      if (this.draggedFolder) {
        if (!this.selectedProjectId) {
          this.draggedFolder = null
          return
        }

        const sourceFolderId = this.draggedFolder.folderId
        this.draggedFolder = null
        const targetPosition = this.nextFolderPosition(status, null)

        this.submitting = true
        this.errorMessage = ''
        try {
          await ApplicationService.updateTaskFolder(this.selectedProjectId, sourceFolderId, {
            parent_folder_id: null,
            position: targetPosition,
          })

          this.updateFolderParentLocally(sourceFolderId, null, targetPosition)
          this.successMessage = 'Folder moved to root.'
        } catch (error: unknown) {
          const typedError = error as { response?: { data?: { message?: string } } }
          this.errorMessage = typedError?.response?.data?.message ?? 'Failed to move folder.'
        } finally {
          this.submitting = false
        }

        return
      }

      if (!this.draggedTask) return

      const target = this.defaultDropTarget(status)
      if (!target) {
        this.errorMessage = 'No folder available in this status for dropping task.'
        this.draggedTask = null
        return
      }

      await this.moveTaskToTarget(this.draggedTask, status, target.folderId, target.categoryId)
      this.draggedTask = null
    },
    nextFolderPosition(status: ApplicationTaskStatus, parentFolderId: number | null): number {
      const siblings = this.sections[status].filter((folder) => {
        return (folder.parent_folder_id ?? null) === parentFolderId
      })

      if (siblings.length === 0) {
        return 1
      }

      return (
        siblings.reduce((max, folder) => {
          return folder.position > max ? folder.position : max
        }, 0) + 1
      )
    },
    wouldCreateLocalFolderCycle(
      status: ApplicationTaskStatus,
      sourceFolderId: number,
      targetFolderId: number,
    ): boolean {
      const folders = this.sections[status]
      const byId = new Map<number, ProjectTaskBoardFolder>()
      folders.forEach((folder) => byId.set(folder.id, folder))

      let currentId: number | null = targetFolderId
      while (currentId !== null) {
        if (currentId === sourceFolderId) {
          return true
        }

        const current = byId.get(currentId)
        currentId = current?.parent_folder_id ?? null
      }

      return false
    },
    updateFolderParentLocally(
      sourceFolderId: number,
      parentFolderId: number | null,
      position: number,
    ) {
      this.boardStatuses.forEach((boardStatus) => {
        this.sections[boardStatus].forEach((folder) => {
          if (folder.id === sourceFolderId) {
            folder.parent_folder_id = parentFolderId
            folder.position = position
          }
        })
      })

      this.folderOptions = this.folderOptions.map((folder) => {
        if (folder.id !== sourceFolderId) {
          return folder
        }

        return {
          ...folder,
          parent_folder_id: parentFolderId,
        }
      })
    },
    defaultDropTarget(
      status: ApplicationTaskStatus,
    ): { folderId: number; categoryId: number | null } | null {
      const statusFolders = this.sections[status]
      for (const folder of statusFolders) {
        const firstCategory = folder.categories[0]
        if (firstCategory) {
          return { folderId: folder.id, categoryId: firstCategory.id }
        }

        return { folderId: folder.id, categoryId: null }
      }

      for (const folder of this.folderOptions) {
        const firstCategory = folder.categories[0]
        if (firstCategory) {
          return { folderId: folder.id, categoryId: firstCategory.id }
        }

        return { folderId: folder.id, categoryId: null }
      }

      return null
    },
    nextTaskPosition(
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
    ): number {
      const folder = this.sections[status].find((item) => item.id === folderId)
      if (!folder) {
        return 1
      }

      if (categoryId === null) {
        const directTasks = folder.uncategorized_tasks ?? []
        if (directTasks.length === 0) {
          return 1
        }

        return (
          directTasks.reduce((max, task) => {
            return task.position > max ? task.position : max
          }, 0) + 1
        )
      }

      const category = folder.categories.find((item) => item.id === categoryId)
      if (!category || category.tasks.length === 0) {
        return 1
      }

      return (
        category.tasks.reduce((max, task) => {
          return task.position > max ? task.position : max
        }, 0) + 1
      )
    },
    async moveTaskToTarget(
      payload: TaskDragPayload,
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
    ) {
      const isNoop =
        payload.fromStatus === status &&
        payload.fromFolderId === folderId &&
        payload.fromCategoryId === categoryId

      if (isNoop) {
        return
      }

      this.submitting = true
      this.errorMessage = ''
      const targetPosition = this.nextTaskPosition(status, folderId, categoryId)
      try {
        const response = await ApplicationService.updateTask(
          payload.applicationId,
          payload.taskId,
          {
            status,
            task_folder_id: folderId,
            task_category_id: categoryId,
            position: targetPosition,
          },
        )

        const updatedTask = {
          ...payload.task,
          ...(response?.data ?? {}),
          status,
          position: targetPosition,
        } as ProjectTaskBoardTask

        this.removeTaskFromSections(payload.taskId)
        this.insertTaskIntoSection(status, folderId, categoryId, updatedTask)

        this.successMessage = 'Task moved successfully.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to move task.'
      } finally {
        this.submitting = false
      }
    },
    removeTaskFromSections(taskId: number) {
      this.boardStatuses.forEach((status) => {
        this.sections[status].forEach((folder) => {
          folder.uncategorized_tasks = (folder.uncategorized_tasks ?? []).filter(
            (task) => task.id !== taskId,
          )
          folder.categories.forEach((category) => {
            category.tasks = category.tasks.filter((task) => task.id !== taskId)
          })
        })
      })
    },
    insertTaskIntoSection(
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
      task: ProjectTaskBoardTask,
    ) {
      const targetFolder = this.sections[status].find((folder) => folder.id === folderId)
      if (!targetFolder) return

      if (categoryId === null) {
        targetFolder.uncategorized_tasks = [...(targetFolder.uncategorized_tasks ?? []), task].sort(
          (a, b) => a.position - b.position,
        )
        return
      }

      const targetCategory = targetFolder.categories.find((category) => category.id === categoryId)
      if (!targetCategory) return

      targetCategory.tasks = [...targetCategory.tasks, task].sort((a, b) => a.position - b.position)
    },
    async renameFolder(folderId: number, currentName: string) {
      if (!this.selectedProjectId) return

      const nextName = window.prompt('Rename folder', currentName)?.trim()
      if (!nextName || nextName === currentName) {
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.updateTaskFolder(this.selectedProjectId, folderId, {
          name: nextName,
        })
        this.boardStatuses.forEach((status) => {
          this.sections[status].forEach((folder) => {
            if (folder.id === folderId) {
              folder.name = nextName
            }
          })
        })
        this.folderOptions = this.folderOptions.map((folder) => {
          return folder.id === folderId ? { ...folder, name: nextName } : folder
        })
        this.successMessage = 'Folder renamed.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename folder.'
      } finally {
        this.submitting = false
      }
    },
    async deleteFolder(folderId: number, _folderName: string) {
      if (!this.selectedProjectId) return

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.deleteTaskFolder(this.selectedProjectId, folderId)
        this.boardStatuses.forEach((status) => {
          this.sections[status] = this.sections[status]
            .filter((folder) => folder.id !== folderId)
            .map((folder) => {
              if (folder.parent_folder_id === folderId) {
                return { ...folder, parent_folder_id: null }
              }

              return folder
            })
        })

        this.folderOptions = this.folderOptions
          .filter((folder) => folder.id !== folderId)
          .map((folder) => {
            if (folder.parent_folder_id === folderId) {
              return { ...folder, parent_folder_id: null }
            }

            return folder
          })

        if (this.selectedFolderId === folderId) {
          this.selectedFolderId = this.folderOptions[0]?.id ?? null
        }

        this.successMessage = 'Folder deleted.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete folder.'
      } finally {
        this.submitting = false
      }
    },
    async renameCategory(folderId: number, categoryId: number, currentName: string) {
      if (!this.selectedProjectId) return

      const nextName = window.prompt('Rename subfolder', currentName)?.trim()
      if (!nextName || nextName === currentName) {
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.updateTaskCategory(this.selectedProjectId, folderId, categoryId, {
          name: nextName,
        })
        this.boardStatuses.forEach((status) => {
          this.sections[status].forEach((folder) => {
            if (folder.id !== folderId) return
            folder.categories = folder.categories.map((category) => {
              return category.id === categoryId ? { ...category, name: nextName } : category
            })
          })
        })

        this.folderOptions = this.folderOptions.map((folder) => {
          if (folder.id !== folderId) return folder

          return {
            ...folder,
            categories: folder.categories.map((category) => {
              return category.id === categoryId ? { ...category, name: nextName } : category
            }),
          }
        })

        this.successMessage = 'Subfolder renamed.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename subfolder.'
      } finally {
        this.submitting = false
      }
    },
    async deleteCategory(folderId: number, categoryId: number, _categoryName: string) {
      if (!this.selectedProjectId) return

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.deleteTaskCategory(this.selectedProjectId, folderId, categoryId)
        this.boardStatuses.forEach((status) => {
          this.sections[status].forEach((folder) => {
            if (folder.id !== folderId) return
            folder.categories = folder.categories.filter((category) => category.id !== categoryId)
          })
        })

        this.folderOptions = this.folderOptions.map((folder) => {
          if (folder.id !== folderId) return folder

          return {
            ...folder,
            categories: folder.categories.filter((category) => category.id !== categoryId),
          }
        })

        this.successMessage = 'Subfolder deleted.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete subfolder.'
      } finally {
        this.submitting = false
      }
    },
    async renameTask(applicationId: number, taskId: number, currentTitle: string) {
      const nextTitle = window.prompt('Rename task', currentTitle)?.trim()
      if (!nextTitle || nextTitle === currentTitle) {
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.updateTask(applicationId, taskId, { title: nextTitle })
        this.boardStatuses.forEach((status) => {
          this.sections[status].forEach((folder) => {
            folder.uncategorized_tasks = (folder.uncategorized_tasks ?? []).map((task) => {
              return task.id === taskId ? { ...task, title: nextTitle } : task
            })

            folder.categories.forEach((category) => {
              category.tasks = category.tasks.map((task) => {
                return task.id === taskId ? { ...task, title: nextTitle } : task
              })
            })
          })
        })
        this.successMessage = 'Task renamed.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename task.'
      } finally {
        this.submitting = false
      }
    },
    async deleteTask(applicationId: number, taskId: number, _taskTitle: string) {
      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.deleteTask(applicationId, taskId)
        this.removeTaskFromSections(taskId)
        this.successMessage = 'Task deleted.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete task.'
      } finally {
        this.submitting = false
      }
    },
    async createFolder() {
      if (!this.selectedProjectId) return
      const name = this.newFolderName.trim()
      if (!name) {
        this.errorMessage = 'Folder name is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        const response = await ApplicationService.createTaskFolder(this.selectedProjectId, { name })
        const created = response?.data as {
          id: number
          name: string
          position?: number
          parent_folder_id?: number | null
        }

        const nextFolder: ProjectTaskBoardFolder = {
          id: created.id,
          name: created.name,
          position: created.position ?? 0,
          parent_folder_id: created.parent_folder_id ?? null,
          uncategorized_tasks: [],
          categories: [],
        }

        this.boardStatuses.forEach((status) => {
          this.sections[status] = [
            ...this.sections[status],
            { ...nextFolder, categories: [], uncategorized_tasks: [] },
          ]
        })
        this.folderOptions = [
          ...this.folderOptions,
          {
            id: nextFolder.id,
            name: nextFolder.name,
            parent_folder_id: nextFolder.parent_folder_id,
            categories: [],
          },
        ]
        if (!this.selectedFolderId) {
          this.selectedFolderId = nextFolder.id
        }
        this.successMessage = 'Folder created.'
        this.newFolderName = ''
        this.openCreateFolder = false
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create folder.'
      } finally {
        this.submitting = false
      }
    },
    async createCategory() {
      if (!this.selectedProjectId || !this.selectedFolderId) return
      const name = this.newCategoryName.trim()
      if (!name) {
        this.errorMessage = 'Category name is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        const response = await ApplicationService.createTaskCategory(
          this.selectedProjectId,
          this.selectedFolderId,
          {
            name,
          },
        )
        const created = response?.data as {
          id: number
          task_folder_id: number
          name: string
          position?: number
        }

        this.boardStatuses.forEach((status) => {
          this.sections[status].forEach((folder) => {
            if (folder.id !== created.task_folder_id) return
            folder.categories = [
              ...folder.categories,
              {
                id: created.id,
                name: created.name,
                position: created.position ?? 0,
                tasks: [],
              },
            ]
          })
        })

        this.folderOptions = this.folderOptions.map((folder) => {
          if (folder.id !== created.task_folder_id) return folder
          return {
            ...folder,
            categories: [...folder.categories, { id: created.id, name: created.name }],
          }
        })

        this.successMessage = 'Category created.'
        this.newCategoryName = ''
        this.openCreateCategory = false
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create category.'
      } finally {
        this.submitting = false
      }
    },
  },
})
</script>

<template>
  <div
    v-if="canManageTasks && openCreateFolder"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
  >
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create folder</h3>
      <select
        :value="createFolderStatus"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onCreateFolderStatusChange"
      >
        <option value="todo">TO DO</option>
        <option value="in_progress">IN PROGRESS</option>
        <option value="complete">COMPLETED</option>
      </select>
      <input
        :value="newFolderName"
        type="text"
        maxlength="120"
        class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        placeholder="Folder name"
        @input="onNewFolderNameInput"
      />
      <div class="mt-4 flex justify-end gap-2">
        <BaseButton variant="secondary" class="rounded-xl!" @click="$emit('update:openCreateFolder', false)">
          Cancel
        </BaseButton>
        <BaseButton
          variant="primary"
          class="rounded-xl!"
          :loading="submitting"
          @click="$emit('create-folder')"
        >
          Create
        </BaseButton>
      </div>
    </div>
  </div>

  <div
    v-if="canManageTasks && openCreateCategory"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
  >
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create subfolder</h3>

      <select
        :value="createCategoryStatus"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onCreateCategoryStatusChange"
      >
        <option value="todo">TO DO</option>
        <option value="in_progress">IN PROGRESS</option>
        <option value="complete">COMPLETED</option>
      </select>

      <select
        v-if="!createCategoryFolderLocked"
        :value="selectedFolderId ?? ''"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onSelectedFolderChange"
      >
        <option value="">Select folder</option>
        <option
          v-for="folder in availableFoldersForCreateCategory"
          :key="folder.id"
          :value="folder.id"
        >
          {{ folder.name }}
        </option>
      </select>

      <div
        v-else
        class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
      >
        {{ selectedFolderName }}
      </div>

      <input
        :value="newCategoryName"
        type="text"
        maxlength="120"
        class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        placeholder="Category name"
        @input="onNewCategoryNameInput"
      />

      <div class="mt-4 flex justify-end gap-2">
        <BaseButton variant="secondary" class="rounded-xl!" @click="$emit('close-create-category')">
          Cancel
        </BaseButton>
        <BaseButton
          variant="primary"
          class="rounded-xl!"
          :loading="submitting"
          @click="$emit('create-category')"
        >
          Create
        </BaseButton>
      </div>
    </div>
  </div>

  <div
    v-if="canManageTasks && openCreateTask"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
  >
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create task</h3>

      <select
        :value="createTaskStatus"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onCreateTaskStatusChange"
      >
        <option value="todo">TO DO</option>
        <option value="in_progress">IN PROGRESS</option>
        <option value="complete">COMPLETED</option>
      </select>

      <select
        :value="selectedTaskApplicationId ?? ''"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onSelectedTaskApplicationChange"
      >
        <option v-if="acceptedApplications.length === 0" value="" disabled>
          No confirmed students on this project
        </option>
        <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
          {{ app.student_name }}
        </option>
      </select>

      <input
        :value="newTaskTitle"
        type="text"
        maxlength="160"
        class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        placeholder="Task title"
        @input="onNewTaskTitleInput"
      />

      <select
        :value="newTaskPriority"
        class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
        @change="onNewTaskPriorityChange"
      >
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>

      <div class="mt-4 flex justify-end gap-2">
        <BaseButton variant="secondary" class="rounded-xl!" @click="$emit('update:openCreateTask', false)">
          Cancel
        </BaseButton>
        <BaseButton
          variant="primary"
          class="rounded-xl!"
          :loading="submitting"
          @click="$emit('create-task')"
        >
          Create
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { ApplicationTaskPriority, ApplicationTaskStatus } from '@/services/applications/ApplicationService'

interface AcceptedApplicationOption {
  id: number
  student_name: string
}

interface FolderOption {
  id: number
  name: string
}

export default defineComponent({
  name: 'TaskBoardCreateModals',
  components: {
    BaseButton,
  },
  props: {
    canManageTasks: {
      type: Boolean,
      default: false,
    },
    submitting: {
      type: Boolean,
      default: false,
    },
    openCreateFolder: {
      type: Boolean,
      default: false,
    },
    openCreateCategory: {
      type: Boolean,
      default: false,
    },
    openCreateTask: {
      type: Boolean,
      default: false,
    },
    createFolderStatus: {
      type: String as PropType<ApplicationTaskStatus>,
      required: true,
    },
    createCategoryStatus: {
      type: String as PropType<ApplicationTaskStatus>,
      required: true,
    },
    createTaskStatus: {
      type: String as PropType<ApplicationTaskStatus>,
      required: true,
    },
    createCategoryFolderLocked: {
      type: Boolean,
      default: false,
    },
    selectedFolderId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    selectedTaskApplicationId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    newFolderName: {
      type: String,
      default: '',
    },
    newCategoryName: {
      type: String,
      default: '',
    },
    newTaskTitle: {
      type: String,
      default: '',
    },
    newTaskPriority: {
      type: String as PropType<ApplicationTaskPriority>,
      required: true,
    },
    folderOptions: {
      type: Array as PropType<FolderOption[]>,
      default: () => [],
    },
    availableFoldersForCreateCategory: {
      type: Array as PropType<FolderOption[]>,
      default: () => [],
    },
    acceptedApplications: {
      type: Array as PropType<AcceptedApplicationOption[]>,
      default: () => [],
    },
  },
  emits: [
    'update:openCreateFolder',
    'update:openCreateTask',
    'update:createFolderStatus',
    'update:createCategoryStatus',
    'update:createTaskStatus',
    'update:selectedFolderId',
    'update:selectedTaskApplicationId',
    'update:newFolderName',
    'update:newCategoryName',
    'update:newTaskTitle',
    'update:newTaskPriority',
    'close-create-category',
    'create-folder',
    'create-category',
    'create-task',
  ],
  computed: {
    selectedFolderName(): string {
      return this.folderOptions.find((folder) => folder.id === this.selectedFolderId)?.name ?? 'Selected folder'
    },
  },
  methods: {
    onCreateFolderStatusChange(event: Event) {
      const nextValue = (event.target as HTMLSelectElement).value as ApplicationTaskStatus
      this.$emit('update:createFolderStatus', nextValue)
    },
    onCreateCategoryStatusChange(event: Event) {
      const nextValue = (event.target as HTMLSelectElement).value as ApplicationTaskStatus
      this.$emit('update:createCategoryStatus', nextValue)
    },
    onCreateTaskStatusChange(event: Event) {
      const nextValue = (event.target as HTMLSelectElement).value as ApplicationTaskStatus
      this.$emit('update:createTaskStatus', nextValue)
    },
    onSelectedFolderChange(event: Event) {
      const rawValue = (event.target as HTMLSelectElement).value
      if (!rawValue) {
        this.$emit('update:selectedFolderId', null)
        return
      }

      const nextValue = Number(rawValue)
      this.$emit('update:selectedFolderId', Number.isFinite(nextValue) && nextValue > 0 ? nextValue : null)
    },
    onSelectedTaskApplicationChange(event: Event) {
      const rawValue = (event.target as HTMLSelectElement).value
      if (!rawValue) {
        this.$emit('update:selectedTaskApplicationId', null)
        return
      }

      const nextValue = Number(rawValue)
      this.$emit(
        'update:selectedTaskApplicationId',
        Number.isFinite(nextValue) && nextValue > 0 ? nextValue : null,
      )
    },
    onNewFolderNameInput(event: Event) {
      this.$emit('update:newFolderName', (event.target as HTMLInputElement).value)
    },
    onNewCategoryNameInput(event: Event) {
      this.$emit('update:newCategoryName', (event.target as HTMLInputElement).value)
    },
    onNewTaskTitleInput(event: Event) {
      this.$emit('update:newTaskTitle', (event.target as HTMLInputElement).value)
    },
    onNewTaskPriorityChange(event: Event) {
      const nextValue = (event.target as HTMLSelectElement).value as ApplicationTaskPriority
      this.$emit('update:newTaskPriority', nextValue)
    },
  },
})
</script>

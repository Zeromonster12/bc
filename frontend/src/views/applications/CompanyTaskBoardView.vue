<template>
  <AppLayout>
    <div class="h-full overflow-hidden space-y-3 lg:my-0 xl:ml-0" @click="closeActionMenu">
      <div
        class="grid h-full min-h-0 items-stretch gap-2 xl:grid-cols-[260px_minmax(0,1fr)]"
      >
        <aside
          class="flex h-full flex-col overflow-hidden rounded-3xl border border-[#ddd7ea] bg-[#efedf5] shadow-[0_10px_24px_rgba(77,55,197,0.08)] backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-[0_10px_24px_rgba(2,6,23,0.45)]"
        >
          <div class="border-b border-[#dfd9ee] px-4 py-3 dark:border-slate-700/70">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#77718f] dark:text-slate-400">Task board</p>
            <h2 class="mt-1 text-sm font-semibold text-[#343047] dark:text-slate-100">
              {{ auth.isStudent ? 'My accepted projects' : 'Company projects' }}
            </h2>
          </div>
          <div class="flex-1 space-y-1 overflow-auto p-2">
            <button
              v-for="project in companyProjects"
              :key="project.id"
              type="button"
              :class="[
                'flex w-full items-center justify-between rounded-2xl border px-3 py-2.5 text-left transition',
                selectedProjectId === project.id
                  ? 'border-[#5a42e5] bg-white text-[#2f2952] shadow-[0_8px_18px_rgba(77,55,197,0.18)] dark:border-indigo-400 dark:bg-slate-800 dark:text-slate-100'
                  : 'border-[#ddd7ea] bg-white/90 text-[#3f3a56] hover:border-[#cfc7e4] hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500',
              ]"
              @click="selectProject(project.id)"
              ;
            >
              <span class="truncate text-xs font-semibold">{{ project.title }}</span>
              <span
                :class="[
                  'ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-semibold',
                  selectedProjectId === project.id
                    ? 'bg-[#ede8ff] text-[#4526c9] dark:bg-indigo-500/20 dark:text-indigo-300'
                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                ]"
              >
                {{
                  selectedProjectId === project.id
                    ? statusCount('todo') + statusCount('in_progress') + statusCount('complete')
                    : '.'
                }}
              </span>
            </button>
          </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-col gap-3 overflow-hidden">
          <BaseAlert
            v-if="errorMessage"
            type="error"
            :message="errorMessage"
            dismissible
            @dismiss="errorMessage = ''"
          />

          <div v-if="loading" class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-36 animate-pulse rounded-3xl bg-slate-100 dark:bg-slate-800" />
          </div>

          <div
            v-else-if="!selectedProjectId"
            class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
          >
            Select a project from left sidebar.
          </div>

          <div
            v-else
            class="min-h-0 flex-1 overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-[0_10px_26px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900 dark:shadow-[0_10px_26px_rgba(2,6,23,0.5)]"
          >
            <div class="h-full overflow-y-auto overflow-x-hidden rounded-3xl bg-white p-3 sm:p-4 lg:p-5 dark:bg-slate-900">
            <section
              v-for="(status, index) in boardStatuses"
              :key="status"
              :class="[
                'overflow-visible transition',
                index < boardStatuses.length - 1 ? 'border-b border-slate-200/60 dark:border-slate-700/60' : '',
                isDropZoneActive(status, null, null, 'status') ? 'bg-[#4e3aba]/5 dark:bg-[#4e3aba]/15' : '',
              ]"
              @dragover.prevent="setActiveDropZone(status, null, null, 'status')"
              @dragleave="clearActiveDropZone"
              @drop.prevent="onStatusDrop(status)"
            >
              <div class="flex items-center gap-2 px-3 pb-2 pt-2.5">
                <component :is="statusIcon(status)" :class="statusIconClass(status)" />
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ statusLabel(status) }}</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                  {{ statusCount(status) }}
                </span>
              </div>

              <div
                class="grid grid-cols-[minmax(0,1fr)_160px_130px_130px_86px] items-center gap-3 bg-slate-50/70 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:bg-slate-800/70 dark:text-slate-400"
              >
                <span>Name</span>
                <span>Assignee</span>
                <span>Priority</span>
                <span>Status</span>
                <span v-if="canManageTasks" class="text-right">Actions</span>
              </div>

              <div
                v-if="flattenedFoldersForStatus(status).length === 0"
                class="px-4 py-4"
              >
                <div v-if="canManageTasks" class="mt-3 relative inline-block">
                  <button
                    type="button"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-dashed border-slate-300 bg-white text-slate-500 transition hover:border-[#4e3aba]/40 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:text-indigo-300"
                    @click.stop="toggleActionMenu(`status-empty-add:${status}`)"
                  >
                    <Plus class="h-3.5 w-3.5" />
                  </button>

                  <div
                    v-if="isActionMenuOpen(`status-empty-add:${status}`)"
                    class="absolute left-0 top-[calc(100%+6px)] z-30 min-w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                    @click.stop
                  >
                    <button
                      type="button"
                      class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                      @click.stop="
                        startInlineFolderCreate(status, null);
                        closeActionMenu()
                      "
                    >
                      <FolderPlus class="h-3.5 w-3.5" />
                      New folder
                    </button>
                    <button
                      type="button"
                      class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                      @click.stop="openStatusQuickCreateTask(status)"
                    >
                      <Plus class="h-3.5 w-3.5" />
                      New task
                    </button>
                  </div>
                </div>

                <div
                  v-if="inlineFolderDraft && inlineFolderDraft.status === status && inlineFolderDraft.parentFolderId === null"
                  class="mt-3 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                >
                  <div class="flex items-center gap-2">
                    <input
                      v-model="inlineFolderDraft.name"
                      type="text"
                      maxlength="120"
                      class="h-8 flex-1 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                      placeholder="Folder name"
                      @keydown.enter.prevent="confirmInlineFolderCreate"
                      @keydown.esc.prevent="cancelInlineFolderCreate"
                    />
                    <button
                      type="button"
                      class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                      @click="confirmInlineFolderCreate"
                    >
                      <Check class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                      @click="cancelInlineFolderCreate"
                    >
                      <X class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>

                <div
                  v-if="inlineTaskDraft && inlineTaskDraft.status === status && inlineTaskDraft.folderId === null"
                  class="mt-3 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                >
                  <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_86px] items-center gap-2">
                    <input
                      v-model="inlineTaskDraft.title"
                      type="text"
                      maxlength="160"
                      class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                      placeholder="Task title"
                      @keydown.enter.prevent="confirmInlineTaskCreate"
                      @keydown.esc.prevent="cancelInlineTaskCreate"
                    />
                    <select
                      v-model="inlineTaskDraft.applicationId"
                      class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                    >
                      <option v-if="acceptedApplications.length === 0" :value="null" disabled>
                        No confirmed students
                      </option>
                      <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
                        {{ app.student_name }}
                      </option>
                    </select>
                    <select
                      v-model="inlineTaskDraft.priority"
                      class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                    <div class="flex justify-end gap-1">
                      <button
                        type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                        @click="confirmInlineTaskCreate"
                      >
                        <Check class="h-3.5 w-3.5" />
                      </button>
                      <button
                        type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                        @click="cancelInlineTaskCreate"
                      >
                        <X class="h-3.5 w-3.5" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="px-2 py-1.5">
                <div
                  v-for="entry in flattenedFoldersForStatus(status)"
                  :key="`folder-${status}-${entry.folder.id}`"
                  class="rounded-lg border border-transparent transition hover:border-slate-200/80"
                  @dragover.prevent="setActiveDropZone(status, entry.folder.is_virtual ? null : entry.folder.id, null, 'folder')"
                  @dragleave="clearActiveDropZone"
                  @drop.prevent.stop="onFolderDrop(status, entry.folder.id)"
                >
                  <div
                    v-if="!entry.folder.is_virtual"
                    :class="[
                      'group flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left transition hover:bg-slate-100/80 dark:hover:bg-slate-700/60',
                      entry.depth === 0
                        ? 'bg-white dark:bg-slate-800/70'
                        : entry.depth === 1
                          ? 'bg-slate-50/70 dark:bg-slate-800/60'
                          : 'bg-slate-100/70 dark:bg-slate-800/50',
                      isDropZoneActive(status, entry.folder.is_virtual ? null : entry.folder.id, null, 'folder')
                        ? 'bg-[#4e3aba]/10 ring-2 ring-[#4e3aba]/25'
                        : '',
                    ]"
                    :draggable="!entry.folder.is_virtual"
                    :style="{ paddingLeft: `${entry.depth * 18 + 10}px` }"
                    role="button"
                    tabindex="0"
                    @dragstart="!entry.folder.is_virtual && onFolderDragStart(status, entry.folder.id, $event)"
                    @dragend="clearActiveDropZone"
                    @click="toggleFolder(status, entry.folder.id)"
                    @keydown.enter.prevent="toggleFolder(status, entry.folder.id)"
                    @keydown.space.prevent="toggleFolder(status, entry.folder.id)"
                  >
                    <span class="flex items-center gap-2">
                      <ChevronRight
                        v-if="!isFolderOpen(status, entry.folder.id)"
                        class="h-4 w-4 text-slate-500"
                      />
                      <ChevronDown v-else class="h-4 w-4 text-slate-500" />
                      <Folder
                        v-if="!isFolderOpen(status, entry.folder.id)"
                        class="h-4 w-4 text-[#4e3aba]"
                        style="fill:#4e3aba;stroke:#4e3aba;stroke-width:1.7"
                      />
                      <FolderOpen v-else class="h-4 w-4 text-[#4e3aba]" style="fill:#4e3aba;stroke:#4e3aba;stroke-width:1.7" />
                      <span
                        v-if="editingFolderId !== entry.folder.id"
                        class="text-sm font-semibold text-slate-800 dark:text-slate-100"
                      >
                        {{ entry.folder.name }}
                      </span>
                      <div v-else class="flex items-center gap-1" @click.stop>
                        <input
                          v-model="folderRenameDraft"
                          type="text"
                          maxlength="120"
                          class="h-7 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                          @keydown.enter.prevent="saveFolderRename(entry.folder.id)"
                          @keydown.esc.prevent="cancelFolderRename"
                        />
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                          @click.stop="saveFolderRename(entry.folder.id)"
                        >
                          <Check class="h-3.5 w-3.5" />
                        </button>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                          @click.stop="cancelFolderRename"
                        >
                          <X class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </span>
                    <span class="flex items-center gap-2">
                      <span class="text-xs text-slate-500">{{ folderTaskCount(entry.folder) }}</span>
                      <span
                        v-if="canManageTasks && editingFolderId !== entry.folder.id && !entry.folder.is_virtual"
                        class="flex items-center gap-1 opacity-0 pointer-events-none transition group-hover:opacity-100 group-hover:pointer-events-auto"
                      >
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-[#4e3aba]/35 hover:bg-slate-50 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                          @click.stop="startFolderRename(entry.folder.id, entry.folder.name)"
                        >
                          <Pencil class="h-3.5 w-3.5" />
                        </button>
                        <button
                          v-if="canManageTasks"
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50"
                          @click.stop="deleteFolder(entry.folder.id, entry.folder.name)"
                        >
                          <Trash2 class="h-3.5 w-3.5" />
                        </button>
                      </span>
                    </span>
                  </div>

                  <div
                    v-if="entry.folder.is_virtual || isFolderOpen(status, entry.folder.id)"
                    :class="entry.folder.is_virtual ? '' : 'border-l border-slate-300/80 pl-2.5 dark:border-slate-700'"
                    :style="entry.folder.is_virtual ? undefined : { marginLeft: `${entry.depth * 18 + 30}px` }"
                  >
                    <div
                      class="mb-1.5 space-y-1 rounded-lg transition"
                      @dragover.prevent="setActiveDropZone(status, entry.folder.is_virtual ? null : entry.folder.id, null, 'folder')"
                      @dragleave="clearActiveDropZone"
                      @drop.prevent.stop="onTaskDrop(status, entry.folder.id, null)"
                    >
                      <div
                        v-for="task in filteredTasks(entry.folder.uncategorized_tasks)"
                        :key="`task-folder-${task.id}`"
                        :class="[
                          'group cursor-grab rounded-md border px-2.5 py-1.5 transition active:cursor-grabbing',
                          entry.depth === 0
                            ? 'border-slate-200 bg-slate-50/40 hover:border-slate-300 hover:bg-white dark:border-slate-700 dark:bg-slate-800/55 dark:hover:border-slate-600 dark:hover:bg-slate-800'
                            : entry.depth === 1
                              ? 'border-slate-200/90 bg-slate-100/55 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/65 dark:hover:border-slate-600 dark:hover:bg-slate-800/80'
                              : 'border-slate-300/80 bg-slate-100/80 hover:border-slate-400 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/75 dark:hover:border-slate-600 dark:hover:bg-slate-800/90',
                          isDraggingTask(task.id) ? 'scale-[0.98] opacity-80 shadow-lg' : '',
                        ]"
                        :draggable="true"
                        @dragstart="onTaskDragStart(task, status, entry.folder.id, null, $event)"
                        @dragend="clearActiveDropZone"
                      >
                        <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_130px_86px] items-center gap-3">
                          <div class="min-w-0">
                            <p
                              v-if="editingTaskId !== task.id"
                              class="truncate text-sm font-medium text-slate-800 transition group-hover:text-[#4e3aba] dark:text-slate-100 dark:group-hover:text-indigo-300"
                            >
                              {{ task.title }}
                            </p>
                            <div v-else class="flex items-center gap-1" @click.stop>
                              <input
                                v-model="taskRenameDraft"
                                type="text"
                                maxlength="160"
                                class="h-7 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                                @keydown.enter.prevent="
                                  saveTaskRename(task.application_id, task.id)
                                "
                                @keydown.esc.prevent="cancelTaskRename"
                              />
                              <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                                @click.stop="saveTaskRename(task.application_id, task.id)"
                              >
                                <Check class="h-3.5 w-3.5" />
                              </button>
                              <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                @click.stop="cancelTaskRename"
                              >
                                <X class="h-3.5 w-3.5" />
                              </button>
                            </div>
                          </div>

                          <div class="min-w-0">
                            <span class="truncate text-xs text-slate-700 dark:text-slate-300">
                              {{ task.assignee.name || task.assignee.email || 'Unknown assignee' }}
                            </span>
                          </div>

                          <div>
                            <span :class="priorityPillClass(task.priority)">
                              {{ priorityLabel(task.priority) }}
                            </span>
                          </div>

                          <div>
                            <span :class="statusPillClass(status)">
                              {{ statusLabel(status) }}
                            </span>
                          </div>

                          <div class="flex justify-end gap-1 opacity-0 pointer-events-none transition group-hover:opacity-100 group-hover:pointer-events-auto">
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-[#4e3aba]/35 hover:bg-slate-50 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                              @click.stop="startTaskRename(task.application_id, task.id, task.title)"
                            >
                              <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button
                              v-if="canManageTasks"
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50"
                              @click.stop="deleteTask(task.application_id, task.id, task.title)"
                            >
                              <Trash2 class="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>



                    <div
                      v-for="category in entry.folder.categories"
                      :key="`cat-${status}-${category.id}`"
                      class="py-1 rounded-lg transition"
                      @dragover.prevent="
                        setActiveDropZone(status, entry.folder.id, category.id, 'category')
                      "
                      @dragleave="clearActiveDropZone"
                      @drop.prevent.stop="onCategoryDrop(status, entry.folder.id, category.id)"
                    >
                      <div
                        :class="[
                          'group flex w-full items-center justify-between rounded-lg px-2 py-1 text-left transition hover:bg-slate-100/80 dark:hover:bg-slate-700/60',
                          entry.depth > 0 ? 'bg-slate-50/80 dark:bg-slate-800/65' : 'bg-slate-50/40 dark:bg-slate-800/50',
                          isDropZoneActive(status, entry.folder.id, category.id, 'category')
                            ? 'bg-[#4e3aba]/10 ring-2 ring-[#4e3aba]/25'
                            : '',
                        ]"
                        role="button"
                        tabindex="0"
                        @click="toggleCategory(status, category.id)"
                        @keydown.enter.prevent="toggleCategory(status, category.id)"
                        @keydown.space.prevent="toggleCategory(status, category.id)"
                      >
                        <span class="flex items-center gap-2">
                          <ChevronRight
                            v-if="!isCategoryOpen(status, category.id)"
                            class="h-3.5 w-3.5 text-slate-500"
                          />
                          <ChevronDown v-else class="h-3.5 w-3.5 text-slate-500" />
                          <Folder class="h-3.5 w-3.5 text-[#4e3aba]" style="fill:#4e3aba;stroke:#4e3aba;stroke-width:1.7" />
                          <span
                            v-if="editingCategoryId !== category.id"
                            class="text-xs font-semibold uppercase tracking-wide text-slate-600"
                          >
                            {{ category.name }}
                          </span>
                          <div v-else class="flex items-center gap-1" @click.stop>
                            <input
                              v-model="categoryRenameDraft"
                              type="text"
                              maxlength="120"
                              class="h-7 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                              @keydown.enter.prevent="
                                saveCategoryRename(entry.folder.id, category.id)
                              "
                              @keydown.esc.prevent="cancelCategoryRename"
                            />
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                              @click.stop="saveCategoryRename(entry.folder.id, category.id)"
                            >
                              <Check class="h-3.5 w-3.5" />
                            </button>
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                              @click.stop="cancelCategoryRename"
                            >
                              <X class="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </span>
                        <span class="flex items-center gap-2">
                          <span class="text-xs text-slate-500">{{ category.tasks.length }}</span>
                          <span
                            v-if="canManageTasks && editingCategoryId !== category.id"
                            class="flex items-center gap-1 opacity-0 pointer-events-none transition group-hover:opacity-100 group-hover:pointer-events-auto"
                          >
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-[#4e3aba]/35 hover:bg-slate-50 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                              @click.stop="startCategoryRename(entry.folder.id, category.id, category.name)"
                            >
                              <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button
                              v-if="canManageTasks"
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50"
                              @click.stop="deleteCategory(entry.folder.id, category.id, category.name)"
                            >
                              <Trash2 class="h-3.5 w-3.5" />
                            </button>
                          </span>
                        </span>
                      </div>

                      <div
                        v-if="isCategoryOpen(status, category.id)"
                        class="ml-5 mt-1 space-y-1 border-l border-slate-300/80 pl-2.5 dark:border-slate-700"
                        @dragover.prevent
                        @drop.prevent.stop="onCategoryDrop(status, entry.folder.id, category.id)"
                      >
                        <div
                          v-for="task in filteredTasks(category.tasks)"
                          :key="`task-${task.id}`"
                          :class="[
                              'group cursor-grab rounded-md border px-2.5 py-1.5 transition active:cursor-grabbing',
                              entry.depth === 0
                                ? 'border-slate-200 bg-slate-50/35 hover:border-slate-300 hover:bg-white dark:border-slate-700 dark:bg-slate-800/55 dark:hover:border-slate-600 dark:hover:bg-slate-800'
                                : entry.depth === 1
                                  ? 'border-slate-200/90 bg-slate-100/60 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/65 dark:hover:border-slate-600 dark:hover:bg-slate-800/80'
                                  : 'border-slate-300/80 bg-slate-100/85 hover:border-slate-400 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/75 dark:hover:border-slate-600 dark:hover:bg-slate-800/90',
                            isDraggingTask(task.id) ? 'scale-[0.98] opacity-80 shadow-lg' : '',
                          ]"
                          :draggable="true"
                          @dragstart="
                            onTaskDragStart(task, status, entry.folder.id, category.id, $event)
                          "
                          @dragend="clearActiveDropZone"
                        >
                          <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_130px_86px] items-center gap-3">
                            <div class="min-w-0">
                              <p
                                v-if="editingTaskId !== task.id"
                                class="truncate text-sm font-medium text-slate-800 transition group-hover:text-[#4e3aba] dark:text-slate-100 dark:group-hover:text-indigo-300"
                              >
                                {{ task.title }}
                              </p>
                              <div v-else class="flex items-center gap-1" @click.stop>
                                <input
                                  v-model="taskRenameDraft"
                                  type="text"
                                  maxlength="160"
                                  class="h-7 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                                  @keydown.enter.prevent="
                                    saveTaskRename(task.application_id, task.id)
                                  "
                                  @keydown.esc.prevent="cancelTaskRename"
                                />
                                <button
                                  type="button"
                                  class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                                  @click.stop="saveTaskRename(task.application_id, task.id)"
                                >
                                  <Check class="h-3.5 w-3.5" />
                                </button>
                                <button
                                  type="button"
                                  class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                  @click.stop="cancelTaskRename"
                                >
                                  <X class="h-3.5 w-3.5" />
                                </button>
                              </div>
                            </div>

                            <div class="min-w-0">
                              <span class="truncate text-xs text-slate-700 dark:text-slate-300">
                                {{ task.assignee.name || task.assignee.email || 'Unknown assignee' }}
                              </span>
                            </div>

                            <div>
                              <span :class="priorityPillClass(task.priority)">
                                {{ priorityLabel(task.priority) }}
                              </span>
                            </div>

                            <div>
                              <span :class="statusPillClass(status)">
                                {{ statusLabel(status) }}
                              </span>
                            </div>

                            <div class="flex justify-end gap-1 opacity-0 pointer-events-none transition group-hover:opacity-100 group-hover:pointer-events-auto">
                              <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-[#4e3aba]/35 hover:bg-slate-50 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                                @click.stop="startTaskRename(task.application_id, task.id, task.title)"
                              >
                                <Pencil class="h-3.5 w-3.5" />
                              </button>
                              <button
                                v-if="canManageTasks"
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50"
                                @click.stop="deleteTask(task.application_id, task.id, task.title)"
                              >
                                <Trash2 class="h-3.5 w-3.5" />
                              </button>
                            </div>
                          </div>
                        </div>

                        <p
                          v-if="filteredTasks(category.tasks).length === 0"
                          class="px-2 py-1 text-xs text-slate-400"
                        >
                          Empty category
                        </p>

                        <div v-if="canManageTasks" class="pt-1">
                          <div class="relative inline-block">
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-dashed border-slate-300 bg-white text-slate-500 transition hover:border-[#4e3aba]/40 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:text-indigo-300"
                              @click.stop="toggleActionMenu(`category-add:${status}:${entry.folder.id}:${category.id}`)"
                            >
                              <Plus class="h-3.5 w-3.5" />
                            </button>

                            <div
                              v-if="isActionMenuOpen(`category-add:${status}:${entry.folder.id}:${category.id}`)"
                              class="absolute left-0 top-[calc(100%+6px)] z-30 min-w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                              @click.stop
                            >
                              <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                @click.stop="
                                  startInlineFolderCreate(status, entry.folder.id);
                                  closeActionMenu()
                                "
                              >
                                <FolderPlus class="h-3.5 w-3.5" />
                                New folder
                              </button>
                              <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                @click.stop="
                                  startInlineTaskCreate(status, entry.folder.id, category.id);
                                  closeActionMenu()
                                "
                              >
                                <Plus class="h-3.5 w-3.5" />
                                New task
                              </button>
                            </div>
                          </div>

                          <div
                            v-if="inlineTaskDraft && inlineTaskDraft.status === status && inlineTaskDraft.folderId === entry.folder.id && inlineTaskDraft.categoryId === category.id"
                            class="mt-2 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                          >
                            <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_86px] items-center gap-2">
                              <input
                                v-model="inlineTaskDraft.title"
                                type="text"
                                maxlength="160"
                                class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                                placeholder="Task title"
                                @keydown.enter.prevent="confirmInlineTaskCreate"
                                @keydown.esc.prevent="cancelInlineTaskCreate"
                              />
                              <select
                                v-model="inlineTaskDraft.applicationId"
                                class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                              >
                                <option v-if="acceptedApplications.length === 0" :value="null" disabled>
                                  No confirmed students
                                </option>
                                <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
                                  {{ app.student_name }}
                                </option>
                              </select>
                              <select
                                v-model="inlineTaskDraft.priority"
                                class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                              >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                              </select>
                              <div class="flex justify-end gap-1">
                                <button
                                  type="button"
                                  class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                                  @click="confirmInlineTaskCreate"
                                >
                                  <Check class="h-3.5 w-3.5" />
                                </button>
                                <button
                                  type="button"
                                  class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                  @click="cancelInlineTaskCreate"
                                >
                                  <X class="h-3.5 w-3.5" />
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-if="canManageTasks && !entry.folder.is_virtual" class="pt-1">
                      <div class="relative inline-block">
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-dashed border-slate-300 bg-white text-slate-500 transition hover:border-[#4e3aba]/40 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:text-indigo-300"
                          @click.stop="toggleActionMenu(`folder-add:${status}:${entry.folder.id}`)"
                        >
                          <Plus class="h-3.5 w-3.5" />
                        </button>

                        <div
                          v-if="isActionMenuOpen(`folder-add:${status}:${entry.folder.id}`)"
                          class="absolute left-0 top-[calc(100%+6px)] z-30 min-w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                          @click.stop
                        >
                          <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                            @click.stop="
                              startInlineFolderCreate(status, entry.folder.id);
                              closeActionMenu()
                            "
                          >
                            <FolderPlus class="h-3.5 w-3.5" />
                            New folder
                          </button>
                          <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                            @click.stop="
                              startInlineTaskCreate(status, entry.folder.id, null);
                              closeActionMenu()
                            "
                          >
                            <Plus class="h-3.5 w-3.5" />
                            New task
                          </button>
                        </div>
                      </div>

                      <div
                        v-if="inlineFolderDraft && inlineFolderDraft.status === status && inlineFolderDraft.parentFolderId === entry.folder.id"
                        class="mt-2 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                      >
                        <div class="flex items-center gap-2">
                          <input
                            v-model="inlineFolderDraft.name"
                            type="text"
                            maxlength="120"
                            class="h-8 flex-1 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                            placeholder="Subfolder name"
                            @keydown.enter.prevent="confirmInlineFolderCreate"
                            @keydown.esc.prevent="cancelInlineFolderCreate"
                          />
                          <button
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                            @click="confirmInlineFolderCreate"
                          >
                            <Check class="h-3.5 w-3.5" />
                          </button>
                          <button
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                            @click="cancelInlineFolderCreate"
                          >
                            <X class="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </div>

                      <div
                        v-if="inlineTaskDraft && inlineTaskDraft.status === status && inlineTaskDraft.folderId === entry.folder.id && inlineTaskDraft.categoryId === null"
                        class="mt-2 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                      >
                        <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_86px] items-center gap-2">
                          <input
                            v-model="inlineTaskDraft.title"
                            type="text"
                            maxlength="160"
                            class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                            placeholder="Task title"
                            @keydown.enter.prevent="confirmInlineTaskCreate"
                            @keydown.esc.prevent="cancelInlineTaskCreate"
                          />
                          <select
                            v-model="inlineTaskDraft.applicationId"
                            class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                          >
                            <option v-if="acceptedApplications.length === 0" :value="null" disabled>
                              No confirmed students
                            </option>
                            <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
                              {{ app.student_name }}
                            </option>
                          </select>
                          <select
                            v-model="inlineTaskDraft.priority"
                            class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                          >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                          </select>
                          <div class="flex justify-end gap-1">
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                              @click="confirmInlineTaskCreate"
                            >
                              <Check class="h-3.5 w-3.5" />
                            </button>
                            <button
                              type="button"
                              class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                              @click="cancelInlineTaskCreate"
                            >
                              <X class="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="canManageTasks" class="pt-1.5">
                  <div class="relative inline-block">
                    <button
                      type="button"
                      class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-dashed border-slate-300 bg-white text-slate-500 transition hover:border-[#4e3aba]/40 hover:text-[#4e3aba] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-400/60 dark:hover:text-indigo-300"
                      @click.stop="toggleActionMenu(`section-end-add:${status}`)"
                    >
                      <Plus class="h-3.5 w-3.5" />
                    </button>

                    <div
                      v-if="isActionMenuOpen(`section-end-add:${status}`)"
                      class="absolute left-0 top-[calc(100%+6px)] z-30 min-w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                      @click.stop
                    >
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click.stop="
                          startInlineFolderCreate(status, null);
                          closeActionMenu()
                        "
                      >
                        <FolderPlus class="h-3.5 w-3.5" />
                        New folder
                      </button>
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click.stop="openStatusQuickCreateTask(status)"
                      >
                        <Plus class="h-3.5 w-3.5" />
                        New task
                      </button>
                    </div>
                  </div>

                  <div
                    v-if="inlineFolderDraft && inlineFolderDraft.status === status && inlineFolderDraft.parentFolderId === null"
                    class="mt-2 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                  >
                    <div class="flex items-center gap-2">
                      <input
                        v-model="inlineFolderDraft.name"
                        type="text"
                        maxlength="120"
                        class="h-8 flex-1 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                        placeholder="Folder name"
                        @keydown.enter.prevent="confirmInlineFolderCreate"
                        @keydown.esc.prevent="cancelInlineFolderCreate"
                      />
                      <button
                        type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                        @click="confirmInlineFolderCreate"
                      >
                        <Check class="h-3.5 w-3.5" />
                      </button>
                      <button
                        type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                        @click="cancelInlineFolderCreate"
                      >
                        <X class="h-3.5 w-3.5" />
                      </button>
                    </div>
                  </div>

                  <div
                    v-if="inlineTaskDraft && inlineTaskDraft.status === status && inlineTaskDraft.folderId === null"
                    class="mt-2 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/70"
                  >
                    <div class="grid grid-cols-[minmax(0,1fr)_160px_130px_86px] items-center gap-2">
                      <input
                        v-model="inlineTaskDraft.title"
                        type="text"
                        maxlength="160"
                        class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                        placeholder="Task title"
                        @keydown.enter.prevent="confirmInlineTaskCreate"
                        @keydown.esc.prevent="cancelInlineTaskCreate"
                      />
                      <select
                        v-model="inlineTaskDraft.applicationId"
                        class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                      >
                        <option v-if="acceptedApplications.length === 0" :value="null" disabled>
                          No confirmed students
                        </option>
                        <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
                          {{ app.student_name }}
                        </option>
                      </select>
                      <select
                        v-model="inlineTaskDraft.priority"
                        class="h-8 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-700 outline-none focus:border-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500"
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                      </select>
                      <div class="flex justify-end gap-1">
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-emerald-600 hover:bg-emerald-50"
                          @click="confirmInlineTaskCreate"
                        >
                          <Check class="h-3.5 w-3.5" />
                        </button>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                          @click="cancelInlineTaskCreate"
                        >
                          <X class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </section>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="canManageTasks && openCreateFolder"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create folder</h3>
          <select
            v-model="createFolderStatus"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option value="todo">TO DO</option>
            <option value="in_progress">IN PROGRESS</option>
            <option value="complete">COMPLETED</option>
          </select>
          <input
            v-model="newFolderName"
            type="text"
            maxlength="120"
            class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
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
        v-if="canManageTasks && openCreateCategory"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create subfolder</h3>

          <select
            v-model="createCategoryStatus"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option value="todo">TO DO</option>
            <option value="in_progress">IN PROGRESS</option>
            <option value="complete">COMPLETED</option>
          </select>

          <select
            v-if="!createCategoryFolderLocked"
            v-model="selectedFolderId"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option :value="null">Select folder</option>
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
            {{ folderOptions.find((folder) => folder.id === selectedFolderId)?.name || 'Selected folder' }}
          </div>

          <input
            v-model="newCategoryName"
            type="text"
            maxlength="120"
            class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
            placeholder="Category name"
          />

          <div class="mt-4 flex justify-end gap-2">
            <BaseButton variant="secondary" class="rounded-xl!" @click="closeCreateCategoryModal">
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

      <div
        v-if="canManageTasks && openCreateTask"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4 dark:bg-slate-950/55"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Create task</h3>

          <select
            v-model="createTaskStatus"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option value="todo">TO DO</option>
            <option value="in_progress">IN PROGRESS</option>
            <option value="complete">COMPLETED</option>
          </select>

          <select
            v-model="selectedTaskApplicationId"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option v-if="acceptedApplications.length === 0" :value="null" disabled>
              No confirmed students on this project
            </option>
            <option v-for="app in acceptedApplications" :key="app.id" :value="app.id">
              {{ app.student_name }}
            </option>
          </select>

          <input
            v-model="newTaskTitle"
            type="text"
            maxlength="160"
            class="mt-3 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
            placeholder="Task title"
          />

          <select
            v-model="newTaskPriority"
            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-700"
          >
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>

          <div class="mt-4 flex justify-end gap-2">
            <BaseButton variant="secondary" class="rounded-xl!" @click="openCreateTask = false">
              Cancel
            </BaseButton>
            <BaseButton
              variant="primary"
              class="rounded-xl!"
              :loading="submitting"
              @click="createTask"
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
  Check,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Folder,
  FolderOpen,
  FolderPlus,
  Pencil,
  Plus,
  SlidersHorizontal,
  Trash2,
  X,
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
import { taskPriorityLabel, taskStatusLabel } from '@/services/applications/TaskBoardLabelService'

type SectionMap = Record<ApplicationTaskStatus, ProjectTaskBoardFolder[]>

interface SimpleCategory {
  id: number
  name: string
}

interface FolderOption {
  id: number
  name: string
  status?: ApplicationTaskStatus | null
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
  fromFolderId: number | null
  fromCategoryId: number | null
}

interface FolderDragPayload {
  folderId: number
  fromStatus: ApplicationTaskStatus
}

type DropZoneType = 'status' | 'folder' | 'category'

interface ActiveDropZone {
  status: ApplicationTaskStatus
  folderId: number | null
  categoryId: number | null
  type: DropZoneType
}

interface AcceptedApplicationOption {
  id: number
  student_name: string
}

interface InlineFolderDraft {
  status: ApplicationTaskStatus
  parentFolderId: number | null
  name: string
}

interface InlineTaskDraft {
  status: ApplicationTaskStatus
  folderId: number | null
  categoryId: number | null
  title: string
  applicationId: number | null
  priority: 'low' | 'medium' | 'high' | 'urgent'
}

export default defineComponent({
  name: 'CompanyTaskBoardView',
  components: {
    AppLayout,
    BaseAlert,
    BaseButton,
    Check,
    Pencil,
    FolderPlus,
    Plus,
    SlidersHorizontal,
    Trash2,
    Folder,
    FolderOpen,
    ChevronRight,
    ChevronDown,
    CheckCircle2,
    X,
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
      openCreateTask: false,
      newFolderName: '',
      newCategoryName: '',
      newTaskTitle: '',
      newTaskPriority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
      selectedFolderId: null as number | null,
      createFolderStatus: 'todo' as ApplicationTaskStatus,
      createCategoryStatus: 'todo' as ApplicationTaskStatus,
      createCategoryFolderLocked: false,
      createTaskStatus: 'todo' as ApplicationTaskStatus,
      createTaskFolderId: null as number | null,
      createTaskCategoryId: null as number | null,
      selectedTaskApplicationId: null as number | null,
      acceptedApplications: [] as AcceptedApplicationOption[],
      folderOptions: [] as FolderOption[],
      draggedTask: null as TaskDragPayload | null,
      draggedFolder: null as FolderDragPayload | null,
      activeDropZone: null as ActiveDropZone | null,
      openActionMenuKey: null as string | null,
      priorityFilter: 'all' as 'all' | 'low' | 'medium' | 'high' | 'urgent',
      editingFolderId: null as number | null,
      folderRenameDraft: '',
      editingCategoryId: null as number | null,
      editingCategoryFolderId: null as number | null,
      categoryRenameDraft: '',
      editingTaskId: null as number | null,
      editingTaskApplicationId: null as number | null,
      taskRenameDraft: '',
      inlineFolderDraft: null as InlineFolderDraft | null,
      inlineTaskDraft: null as InlineTaskDraft | null,
      studentProjects: [] as Array<{ id: number; title: string }>,
      boardStatuses: ['todo', 'in_progress', 'complete'] as ApplicationTaskStatus[],
    }
  },
  computed: {
    canManageTasks(): boolean {
      return !this.auth.isStudent
    },
    companyProjects() {
      if (this.auth.isStudent) {
        return this.studentProjects
      }

      return this.projectStore.projects
    },
    availableFoldersForCreateCategory(): FolderOption[] {
      const currentStatus = this.createCategoryStatus
      const availableIds = new Set(this.sections[currentStatus].map((folder) => folder.id))
      return this.folderOptions.filter((folder) => availableIds.has(folder.id))
    },
  },
  async mounted() {
    // Prevent browser default drop handling (navigation/reload) during board drag & drop.
    window.addEventListener('dragover', this.preventBrowserDrop)
    window.addEventListener('drop', this.preventBrowserDrop)

    this.loading = true
    try {
      if (this.auth.isStudent) {
        const response = await ApplicationService.getAll({ status: 'accepted', per_page: 100 })
        const items = Array.isArray(response?.data) ? response.data : []
        const seenProjectIds = new Set<number>()

        this.studentProjects = items
          .map((item: any): { id: number; title: string } => ({
            id: Number(item?.project?.id ?? 0),
            title: String(item?.project?.title ?? '').trim(),
          }))
          .filter((item: { id: number; title: string }) => item.id > 0 && item.title !== '')
          .filter((item: { id: number; title: string }) => {
            if (seenProjectIds.has(item.id)) {
              return false
            }
            seenProjectIds.add(item.id)
            return true
          })
      } else {
        await this.projectStore.fetchProjects({
          company_id: Number(this.auth.user?.id),
          per_page: 100,
        })
      }

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
  beforeUnmount() {
    window.removeEventListener('dragover', this.preventBrowserDrop)
    window.removeEventListener('drop', this.preventBrowserDrop)
  },
  methods: {
    preventBrowserDrop(event: DragEvent) {
      event.preventDefault()
    },
    async selectProject(projectId: number) {
      if (this.selectedProjectId === projectId) {
        return
      }

      this.selectedProjectId = projectId
      await this.onProjectChange()
    },
    async onProjectChange() {
      if (!this.selectedProjectId) {
        this.sections = { todo: [], in_progress: [], complete: [] }
        this.folderOptions = []
        this.acceptedApplications = []
        this.selectedTaskApplicationId = null
        return
      }
      await this.loadBoard(this.selectedProjectId)
    },
    async loadBoard(projectId: number, showLoading: boolean = true) {
      if (showLoading) {
        this.loading = true
      }
      this.errorMessage = ''
      try {
        const board = await ApplicationService.getProjectTaskBoard(projectId)
        this.sections = board.data.sections
        this.hydrateOpenStates()
        await this.loadFolders(projectId)
        await this.loadAcceptedApplications(projectId)
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to load task board.'
      } finally {
        if (showLoading) {
          this.loading = false
        }
      }
    },
    async loadAcceptedApplications(projectId: number) {
      const response = await ApplicationService.getAll({
        project_id: projectId,
        status: 'accepted',
        per_page: 100,
      })

      const rawItems = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response?.data?.data)
          ? response.data.data
          : []

      this.acceptedApplications = rawItems
        .filter((item: any) => Number(item?.id ?? 0) > 0)
        .map((item: any) => ({
          id: Number(item?.id ?? 0),
          student_name: String(item?.student?.name ?? `Application #${item?.id ?? ''}`).trim(),
        }))

      if (this.acceptedApplications.length === 0) {
        this.selectedTaskApplicationId = null
        return
      }

      const normalizedSelectedId = this.selectedTaskApplicationId !== null
        ? Number(this.selectedTaskApplicationId)
        : null
      const selectedStillValid = normalizedSelectedId !== null
        && this.acceptedApplications.some((application) => application.id === normalizedSelectedId)

      this.selectedTaskApplicationId = selectedStillValid
        ? normalizedSelectedId
        : this.acceptedApplications[0]!.id
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
        status: (folder as { status?: ApplicationTaskStatus | null }).status ?? null,
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
      const directTasks = this.filteredTasks(folder.uncategorized_tasks).length
      return (
        directTasks +
        folder.categories.reduce((sum, category) => {
          return sum + this.filteredTasks(category.tasks).length
        }, 0)
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
      return taskStatusLabel(status)
    },
    statusIcon(status: ApplicationTaskStatus) {
      if (status === 'todo') return 'Folder'
      if (status === 'in_progress') return 'Pencil'
      return 'CheckCircle2'
    },
    statusIconClass(status: ApplicationTaskStatus): string {
      if (status === 'todo') return 'h-4 w-4 text-[#4e3aba]'
      if (status === 'in_progress') return 'h-4 w-4 text-amber-600 dark:text-amber-400'
      return 'h-4 w-4 text-emerald-600 dark:text-emerald-400'
    },
    priorityLabel(priority: string): string {
      return taskPriorityLabel(priority)
    },
    priorityPillClass(priority: string): string {
      if (priority === 'urgent') {
        return 'inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
      }
      if (priority === 'high') {
        return 'inline-flex items-center rounded-full bg-orange-100 px-2 py-1 text-[11px] font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
      }
      if (priority === 'medium') {
        return 'inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
      }
      return 'inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300'
    },
    statusPillClass(status: ApplicationTaskStatus): string {
      if (status === 'todo') {
        return 'inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-300'
      }
      if (status === 'in_progress') {
        return 'inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
      }
      return 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
    },
    onFolderDragStart(status: ApplicationTaskStatus, folderId: number, event: DragEvent) {
      this.closeActionMenu()
      this.activeDropZone = null
      this.draggedFolder = { folderId, fromStatus: status }
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', `folder:${folderId}`)
      }
    },
    async onFolderDrop(status: ApplicationTaskStatus, targetFolderId: number) {
      this.clearActiveDropZone()
      if (!this.selectedProjectId) return

      const normalizedTargetFolderId = targetFolderId > 0 ? targetFolderId : null

      if (this.draggedTask) {
        await this.moveTaskToTarget(this.draggedTask, status, normalizedTargetFolderId, null)
        this.draggedTask = null
        return
      }

      if (!this.draggedFolder) return

      const sourceFolderId = this.draggedFolder.folderId
      this.draggedFolder = null

      if (normalizedTargetFolderId === null) {
        return
      }

      if (sourceFolderId === normalizedTargetFolderId) return

      if (this.wouldCreateLocalFolderCycle(status, sourceFolderId, normalizedTargetFolderId)) {
        this.errorMessage = 'This move would create a folder cycle.'
        return
      }

      const targetPosition = this.nextFolderPosition(status, normalizedTargetFolderId)

      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.updateTaskFolder(
          this.selectedProjectId as number,
          sourceFolderId,
          {
            parent_folder_id: normalizedTargetFolderId,
            position: targetPosition,
            status,
          },
        )

        await this.loadBoard(this.selectedProjectId, false)
        this.openFolders[`${status}:${normalizedTargetFolderId}`] = true
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
      this.closeActionMenu()
      this.activeDropZone = null
      this.draggedTask = {
        taskId: task.id,
        applicationId: task.application_id,
        task,
        fromStatus: status,
        fromFolderId: folderId > 0 ? folderId : null,
        fromCategoryId: categoryId,
      }
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', `task:${task.id}`)
      }
    },
    async onTaskDrop(status: ApplicationTaskStatus, folderId: number | null, categoryId: number | null) {
      this.clearActiveDropZone()
      if (!this.draggedTask) return
      const normalizedFolderId = folderId !== null && folderId > 0 ? folderId : null
      await this.moveTaskToTarget(this.draggedTask, status, normalizedFolderId, categoryId)
      this.draggedTask = null
    },
    async onCategoryDrop(status: ApplicationTaskStatus, folderId: number, categoryId: number) {
      if (this.draggedFolder) {
        await this.onFolderDrop(status, folderId)
        return
      }

      await this.onTaskDrop(status, folderId, categoryId)
    },
    async onStatusDrop(status: ApplicationTaskStatus) {
      this.clearActiveDropZone()
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
            status,
          })

          await this.loadBoard(this.selectedProjectId, false)
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
        await this.moveTaskToTarget(this.draggedTask, status, null, null)
        this.draggedTask = null
        return
      }

      await this.moveTaskToTarget(this.draggedTask, status, target.folderId, target.categoryId)
      this.draggedTask = null
    },
    setActiveDropZone(
      status: ApplicationTaskStatus,
      folderId: number | null,
      categoryId: number | null,
      type: DropZoneType,
    ) {
      this.activeDropZone = { status, folderId, categoryId, type }
    },
    toggleActionMenu(key: string) {
      this.openActionMenuKey = this.openActionMenuKey === key ? null : key
    },
    isActionMenuOpen(key: string): boolean {
      return this.openActionMenuKey === key
    },
    closeActionMenu() {
      this.openActionMenuKey = null
    },
    isDropZoneActive(
      status: ApplicationTaskStatus,
      folderId: number | null,
      categoryId: number | null,
      type: DropZoneType,
    ): boolean {
      if (!this.activeDropZone) {
        return false
      }

      return (
        this.activeDropZone.status === status &&
        this.activeDropZone.folderId === folderId &&
        this.activeDropZone.categoryId === categoryId &&
        this.activeDropZone.type === type
      )
    },
    clearActiveDropZone() {
      this.activeDropZone = null
    },
    filteredTasks(tasks: ProjectTaskBoardTask[] | undefined): ProjectTaskBoardTask[] {
      const source = tasks ?? []
      if (this.priorityFilter === 'all') {
        return source
      }

      return source.filter((task) => task.priority === this.priorityFilter)
    },
    isDraggingTask(taskId: number): boolean {
      return this.draggedTask?.taskId === taskId
    },
    openQuickCreateTask() {
      const target = this.defaultDropTarget('todo')
      if (!target) {
        this.errorMessage = 'Create a folder first to add task.'
        this.closeActionMenu()
        return
      }

      this.startInlineTaskCreate('todo', target.folderId, target.categoryId)
      this.closeActionMenu()
    },
    openStatusQuickCreateTask(status: ApplicationTaskStatus) {
      const target = this.defaultDropTarget(status)
      if (target) {
        this.startInlineTaskCreate(status, target.folderId, target.categoryId)
      } else {
        this.startInlineTaskCreate(status, null, null)
      }
      this.closeActionMenu()
    },
    startInlineFolderCreate(status: ApplicationTaskStatus, parentFolderId: number | null) {
      this.closeActionMenu()
      this.inlineTaskDraft = null
      this.inlineFolderDraft = {
        status,
        parentFolderId,
        name: '',
      }
      this.errorMessage = ''
    },
    cancelInlineFolderCreate() {
      this.inlineFolderDraft = null
    },
    resolveTaskApplicationId(
      status: ApplicationTaskStatus,
      folderId: number | null,
      categoryId: number | null,
    ): number | null {
      if (folderId === null) {
        return this.acceptedApplications[0]?.id ?? null
      }
      const firstTask = this.findFirstTaskInLocation(status, folderId, categoryId)
      return firstTask?.application_id ?? this.acceptedApplications[0]?.id ?? null
    },
    isAcceptedApplicationId(applicationId: number | null): boolean {
      if (applicationId === null) return false
      const normalized = Number(applicationId)
      return this.acceptedApplications.some((application) => application.id === normalized)
    },
    startInlineTaskCreate(
      status: ApplicationTaskStatus,
      folderId: number | null,
      categoryId: number | null,
    ) {
      this.closeActionMenu()
      this.inlineFolderDraft = null

      this.inlineTaskDraft = {
        status,
        folderId,
        categoryId,
        title: '',
        applicationId: this.resolveTaskApplicationId(status, folderId, categoryId),
        priority: 'medium',
      }
      this.errorMessage = ''
    },
    cancelInlineTaskCreate() {
      this.inlineTaskDraft = null
    },
    async confirmInlineFolderCreate() {
      if (!this.canManageTasks) return
      if (!this.inlineFolderDraft || !this.selectedProjectId) return

      const { status, parentFolderId } = this.inlineFolderDraft
      const name = this.inlineFolderDraft.name.trim()
      if (!name) {
        this.errorMessage = 'Folder name is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        if (parentFolderId === null) {
          const response = await ApplicationService.createTaskFolder(this.selectedProjectId, {
            name,
            status,
            position: this.nextFolderPosition(status, null),
          })
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

          this.sections[status] = [
            ...this.sections[status],
            { ...nextFolder, categories: [], uncategorized_tasks: [] },
          ]
          this.folderOptions = [
            ...this.folderOptions,
            {
              id: nextFolder.id,
              name: nextFolder.name,
              status,
              parent_folder_id: nextFolder.parent_folder_id,
              categories: [],
            },
          ]
          this.openFolders[`${status}:${nextFolder.id}`] = true
        } else {
          const response = await ApplicationService.createTaskCategory(
            this.selectedProjectId,
            parentFolderId,
            {
              name,
              position: this.nextCategoryPosition(status, parentFolderId),
            },
          )

          const created = response?.data as {
            id: number
            task_folder_id: number
            name: string
            position?: number
          }

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
            this.openCategories[`${status}:${created.id}`] = true
          })

          this.folderOptions = this.folderOptions.map((folder) => {
            if (folder.id !== created.task_folder_id) return folder
            return {
              ...folder,
              categories: [...folder.categories, { id: created.id, name: created.name }],
            }
          })
        }

        this.inlineFolderDraft = null
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create folder.'
      } finally {
        this.submitting = false
      }
    },
    async confirmInlineTaskCreate() {
      if (!this.canManageTasks) return
      if (!this.inlineTaskDraft) return

      const { status, folderId, categoryId, priority } = this.inlineTaskDraft
      const applicationId = this.inlineTaskDraft.applicationId
      const title = this.inlineTaskDraft.title.trim()

      if (!this.isAcceptedApplicationId(applicationId)) {
        this.errorMessage = 'You can assign task only to a confirmed student on this project.'
        return
      }
      const normalizedApplicationId = Number(applicationId)
      if (!title) {
        this.errorMessage = 'Task title is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        const createdResponse = await ApplicationService.createTask(normalizedApplicationId, {
          title,
          priority,
          task_folder_id: folderId ?? undefined,
          task_category_id: categoryId ?? undefined,
          position: this.nextTaskPosition(status, folderId, categoryId),
        })

        const createdTask = createdResponse?.data as ProjectTaskBoardTask

        let finalTask = {
          ...createdTask,
          title,
          priority,
          task_folder_id: folderId,
          task_category_id: categoryId,
        } as ProjectTaskBoardTask

        if (status !== 'todo') {
          const targetPosition = this.nextTaskPosition(status, folderId, categoryId)
          const updatedResponse = await ApplicationService.updateTask(normalizedApplicationId, createdTask.id, {
            status,
            task_folder_id: folderId,
            task_category_id: categoryId,
            position: targetPosition,
          })

          finalTask = {
            ...finalTask,
            ...(updatedResponse?.data ?? {}),
            status,
            position: targetPosition,
          }
        }

        this.removeTaskFromSections(createdTask.id)
        this.insertTaskIntoSection(status, folderId, categoryId, finalTask)
        if (folderId === null && this.selectedProjectId) {
          await this.loadBoard(this.selectedProjectId, false)
        }
        this.inlineTaskDraft = null
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create task.'
      } finally {
        this.submitting = false
      }
    },
    startFolderRename(folderId: number, currentName: string) {
      this.editingFolderId = folderId
      this.folderRenameDraft = currentName
    },
    cancelFolderRename() {
      this.editingFolderId = null
      this.folderRenameDraft = ''
    },
    async saveFolderRename(folderId: number) {
      if (!this.selectedProjectId) return

      const nextName = this.folderRenameDraft.trim()
      if (!nextName) {
        this.errorMessage = 'Folder name is required.'
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

        this.cancelFolderRename()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename folder.'
      } finally {
        this.submitting = false
      }
    },
    startCategoryRename(folderId: number, categoryId: number, currentName: string) {
      this.editingCategoryFolderId = folderId
      this.editingCategoryId = categoryId
      this.categoryRenameDraft = currentName
    },
    cancelCategoryRename() {
      this.editingCategoryFolderId = null
      this.editingCategoryId = null
      this.categoryRenameDraft = ''
    },
    async saveCategoryRename(folderId: number, categoryId: number) {
      if (!this.selectedProjectId) return

      const nextName = this.categoryRenameDraft.trim()
      if (!nextName) {
        this.errorMessage = 'Subfolder name is required.'
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

        this.cancelCategoryRename()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename subfolder.'
      } finally {
        this.submitting = false
      }
    },
    startTaskRename(applicationId: number, taskId: number, currentTitle: string) {
      this.editingTaskApplicationId = applicationId
      this.editingTaskId = taskId
      this.taskRenameDraft = currentTitle
    },
    cancelTaskRename() {
      this.editingTaskApplicationId = null
      this.editingTaskId = null
      this.taskRenameDraft = ''
    },
    async saveTaskRename(applicationId: number, taskId: number) {
      const nextTitle = this.taskRenameDraft.trim()
      if (!nextTitle) {
        this.errorMessage = 'Task title is required.'
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

        this.cancelTaskRename()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to rename task.'
      } finally {
        this.submitting = false
      }
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
    nextCategoryPosition(status: ApplicationTaskStatus, folderId: number): number {
      const folder = this.sections[status].find((item) => item.id === folderId)
      if (!folder || folder.categories.length === 0) {
        return 1
      }

      return (
        folder.categories.reduce((max, category) => {
          return category.position > max ? category.position : max
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
    openCreateFolderModal(status: ApplicationTaskStatus) {
      this.closeActionMenu()
      this.createFolderStatus = status
      this.openCreateFolder = true
      this.errorMessage = ''
    },
    openCreateCategoryModal(
      status: ApplicationTaskStatus,
      folderId?: number,
      lockFolder: boolean = false,
    ) {
      this.closeActionMenu()
      this.createCategoryStatus = status
      const availableFolders = this.availableFoldersForCreateCategory
      const fallbackFolderId = availableFolders[0]?.id ?? null
      this.selectedFolderId = folderId ?? fallbackFolderId
      this.createCategoryFolderLocked = lockFolder
      this.openCreateCategory = true
      this.errorMessage = ''
    },
    closeCreateCategoryModal() {
      this.openCreateCategory = false
      this.createCategoryFolderLocked = false
    },
    findFirstTaskInLocation(
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
    ): ProjectTaskBoardTask | null {
      const folder = this.sections[status].find((item) => item.id === folderId)
      if (!folder) return null

      if (categoryId === null) {
        return folder.uncategorized_tasks?.[0] ?? null
      }

      const category = folder.categories.find((item) => item.id === categoryId)
      return category?.tasks?.[0] ?? null
    },
    openCreateTaskModal(
      status: ApplicationTaskStatus,
      folderId: number,
      categoryId: number | null,
    ) {
      this.closeActionMenu()
      this.createTaskStatus = status
      this.createTaskFolderId = folderId
      this.createTaskCategoryId = categoryId
      this.newTaskTitle = ''
      this.newTaskPriority = 'medium'
      const firstTask = this.findFirstTaskInLocation(status, folderId, categoryId)
      this.selectedTaskApplicationId =
        firstTask?.application_id ?? this.acceptedApplications[0]?.id ?? null
      this.openCreateTask = true
      this.errorMessage = ''
    },
    defaultDropTarget(
      status: ApplicationTaskStatus,
    ): { folderId: number; categoryId: number | null } | null {
      const statusFolders = this.sections[status].filter((folder) => !folder.is_virtual)
      for (const folder of statusFolders) {
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
      folderId: number | null,
      categoryId: number | null,
    ): number {
      if (folderId === null) {
        const standaloneFolder = this.sections[status].find((item) => item.is_virtual)
        const directTasks = standaloneFolder?.uncategorized_tasks ?? []
        if (directTasks.length === 0) {
          return 1
        }

        return (
          directTasks.reduce((max, task) => {
            return task.position > max ? task.position : max
          }, 0) + 1
        )
      }

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
      folderId: number | null,
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
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to move task.'
      } finally {
        this.submitting = false
      }
    },
    removeTaskFromSections(taskId: number) {
      this.boardStatuses.forEach((status) => {
        const nextFolders: ProjectTaskBoardFolder[] = []

        this.sections[status].forEach((folder) => {
          folder.uncategorized_tasks = (folder.uncategorized_tasks ?? []).filter((task) => {
            return task.id !== taskId
          })
          folder.categories.forEach((category) => {
            category.tasks = category.tasks.filter((task) => {
              return task.id !== taskId
            })
          })

          nextFolders.push(folder)
        })

        this.sections[status] = nextFolders
      })
    },
    insertTaskIntoSection(
      status: ApplicationTaskStatus,
      folderId: number | null,
      categoryId: number | null,
      task: ProjectTaskBoardTask,
    ) {
      if (folderId === null) {
        let standaloneFolder = this.sections[status].find((folder) => folder.is_virtual)
        if (!standaloneFolder) {
          standaloneFolder = {
            id: 0,
            name: 'No folder',
            position: -1,
            parent_folder_id: null,
            is_virtual: true,
            uncategorized_tasks: [],
            categories: [],
          }
          this.sections[status] = [standaloneFolder, ...this.sections[status]]
          this.openFolders[`${status}:0`] = true
        }

        standaloneFolder.uncategorized_tasks = [
          ...(standaloneFolder.uncategorized_tasks ?? []),
          task,
        ].sort((a, b) => a.position - b.position)
        return
      }

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
    async deleteFolder(folderId: number, _folderName: string) {
      if (!this.canManageTasks) return
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
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete folder.'
      } finally {
        this.submitting = false
      }
    },
    async deleteCategory(folderId: number, categoryId: number, _categoryName: string) {
      if (!this.canManageTasks) return
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
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete subfolder.'
      } finally {
        this.submitting = false
      }
    },
    async deleteTask(applicationId: number, taskId: number, _taskTitle: string) {
      if (!this.canManageTasks) return
      this.submitting = true
      this.errorMessage = ''
      try {
        await ApplicationService.deleteTask(applicationId, taskId)
        this.removeTaskFromSections(taskId)
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete task.'
      } finally {
        this.submitting = false
      }
    },
    async createFolder() {
      if (!this.canManageTasks) return
      if (!this.selectedProjectId) return
      const name = this.newFolderName.trim()
      if (!name) {
        this.errorMessage = 'Folder name is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        const response = await ApplicationService.createTaskFolder(this.selectedProjectId, {
          name,
          status: this.createFolderStatus,
          position: this.nextFolderPosition(this.createFolderStatus, null),
        })
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

        this.sections[this.createFolderStatus] = [
          ...this.sections[this.createFolderStatus],
          { ...nextFolder, categories: [], uncategorized_tasks: [] },
        ]
        this.folderOptions = [
          ...this.folderOptions,
          {
            id: nextFolder.id,
            name: nextFolder.name,
            status: this.createFolderStatus,
            parent_folder_id: nextFolder.parent_folder_id,
            categories: [],
          },
        ]
        if (!this.selectedFolderId) {
          this.selectedFolderId = nextFolder.id
        }
        this.openFolders[`${this.createFolderStatus}:${nextFolder.id}`] = true
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
      if (!this.canManageTasks) return
      if (!this.selectedProjectId || !this.selectedFolderId) return
      const availableFolderIds = new Set(
        this.availableFoldersForCreateCategory.map((folder) => folder.id),
      )
      if (!availableFolderIds.has(this.selectedFolderId)) {
        this.errorMessage = 'Select a folder from the selected status section.'
        return
      }
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
            position: this.nextCategoryPosition(this.createCategoryStatus, this.selectedFolderId),
          },
        )
        const created = response?.data as {
          id: number
          task_folder_id: number
          name: string
          position?: number
        }

        this.sections[this.createCategoryStatus].forEach((folder) => {
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
          this.openCategories[`${this.createCategoryStatus}:${created.id}`] = true
        })

        this.folderOptions = this.folderOptions.map((folder) => {
          if (folder.id !== created.task_folder_id) return folder
          return {
            ...folder,
            categories: [...folder.categories, { id: created.id, name: created.name }],
          }
        })

        this.newCategoryName = ''
        this.closeCreateCategoryModal()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create category.'
      } finally {
        this.submitting = false
      }
    },
    async createTask() {
      if (!this.canManageTasks) return
      if (!this.selectedProjectId) return
      if (!this.isAcceptedApplicationId(this.selectedTaskApplicationId)) {
        this.errorMessage = 'You can assign task only to a confirmed student on this project.'
        return
      }
      const normalizedApplicationId = Number(this.selectedTaskApplicationId)

      const title = this.newTaskTitle.trim()
      if (!title) {
        this.errorMessage = 'Task title is required.'
        return
      }

      if (!this.createTaskFolderId) {
        this.errorMessage = 'Target folder is required.'
        return
      }

      this.submitting = true
      this.errorMessage = ''
      try {
        const createdResponse = await ApplicationService.createTask(
          normalizedApplicationId,
          {
            title,
            priority: this.newTaskPriority,
            task_folder_id: this.createTaskFolderId,
            task_category_id: this.createTaskCategoryId ?? undefined,
            position: this.nextTaskPosition(
              this.createTaskStatus,
              this.createTaskFolderId,
              this.createTaskCategoryId,
            ),
          },
        )

        const createdTask = createdResponse?.data as ProjectTaskBoardTask

        let finalTask = {
          ...createdTask,
          title,
          priority: this.newTaskPriority,
          task_folder_id: this.createTaskFolderId,
          task_category_id: this.createTaskCategoryId,
        } as ProjectTaskBoardTask

        if (this.createTaskStatus !== 'todo') {
          const targetPosition = this.nextTaskPosition(
            this.createTaskStatus,
            this.createTaskFolderId,
            this.createTaskCategoryId,
          )
          const updatedResponse = await ApplicationService.updateTask(
            normalizedApplicationId,
            createdTask.id,
            {
              status: this.createTaskStatus,
              task_folder_id: this.createTaskFolderId,
              task_category_id: this.createTaskCategoryId,
              position: targetPosition,
            },
          )

          finalTask = {
            ...finalTask,
            ...(updatedResponse?.data ?? {}),
            status: this.createTaskStatus,
            position: targetPosition,
          }
        }

        this.removeTaskFromSections(createdTask.id)
        this.insertTaskIntoSection(
          this.createTaskStatus,
          this.createTaskFolderId,
          this.createTaskCategoryId,
          finalTask,
        )

        this.newTaskTitle = ''
        this.openCreateTask = false
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to create task.'
      } finally {
        this.submitting = false
      }
    },
  },
})
</script>

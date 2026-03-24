<template>
  <section class="space-y-3">
    <div
      v-if="!selectedProject"
      class="rounded-xl border border-gray-200 bg-white py-16 text-center text-gray-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
    >
      <p class="font-medium">Select a project to view applicants</p>
    </div>

    <template v-else>
      <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-900/90">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ selectedProject.title }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Applicants for this project are listed below.</p>
      </div>

      <div
        v-if="applications.length === 0"
        class="rounded-xl border border-gray-200 bg-white py-16 text-center text-gray-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
      >
        <p class="text-4xl mb-3">📝</p>
        <p class="font-medium">No applicants for this project</p>
      </div>

      <div v-else class="space-y-3">
        <ApplicationCard
          v-for="application in applications"
          :key="application.id"
          :application="application"
        >
          <template #actions>
            <div class="flex gap-2 mt-3" v-if="application.status === 'pending'">
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

            <div v-if="application.status === 'accepted'" class="mt-3 w-full space-y-2">
              <p class="text-xs font-semibold text-gray-700 dark:text-slate-300">Create task for student</p>
              <input
                v-model="taskTitle[application.id]"
                type="text"
                maxlength="160"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                placeholder="Task title"
              />
              <div class="grid gap-2 sm:grid-cols-[160px_1fr]">
                <select
                  v-model="taskPriority[application.id]"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
                <textarea
                  v-model="taskRequirements[application.id]"
                  rows="2"
                  maxlength="5000"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                  placeholder="Requirements for this task"
                />
              </div>
              <BaseButton
                variant="primary"
                size="sm"
                :loading="creatingTaskId === application.id"
                @click="emitCreateTask(application.id)"
              >
                Create task
              </BaseButton>
            </div>
          </template>
        </ApplicationCard>
      </div>
    </template>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ApplicationCard from '@/components/applications/ApplicationCard.vue'
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
  [key: string]: unknown
}

export default defineComponent({
  name: 'CompanyApplicantsPanel',
  components: { ApplicationCard, BaseButton },
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
    creatingTaskId: {
      type: Number as PropType<number | null>,
      default: null,
    },
  },
  emits: ['update-status', 'create-task'],
  data() {
    return {
      taskTitle: {} as Record<number, string>,
      taskPriority: {} as Record<number, 'low' | 'medium' | 'high' | 'urgent'>,
      taskRequirements: {} as Record<number, string>,
    }
  },
  methods: {
    emitCreateTask(applicationId: number) {
      const title = (this.taskTitle[applicationId] ?? '').trim()
      if (!title) return

      const priority = this.taskPriority[applicationId] ?? 'medium'
      const requirements = (this.taskRequirements[applicationId] ?? '').trim()

      this.$emit('create-task', {
        applicationId,
        title,
        priority,
        requirements: requirements || undefined,
      })

      this.taskTitle[applicationId] = ''
      this.taskRequirements[applicationId] = ''
      this.taskPriority[applicationId] = 'medium'
    },
  },
})
</script>

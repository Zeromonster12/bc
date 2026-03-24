<template>
  <AppLayout>
    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <div class="h-8 w-64 rounded bg-gray-100 animate-pulse dark:bg-slate-800" />
      <div class="h-48 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
    </div>

    <!-- Error -->
    <BaseAlert v-else-if="fetchError" type="error" :message="fetchError" class="mt-4" />

    <!-- Content -->
    <template v-else-if="project">
      <div class="space-y-6">
        <ProjectDetailHeader
          :project="project"
          :is-owner-company="auth.isCompany && project.company?.user_id === auth.user?.id"
          :can-student-apply="auth.isStudent && project.status === 'open'"
          :has-applied="hasApplied"
          :deleting="deleting"
          @delete="confirmDelete"
          @open-apply="showApplyModal = true"
        />

        <!-- Details grid -->
        <div class="grid lg:grid-cols-3 gap-6">
          <!-- Main content -->
          <div class="lg:col-span-2 space-y-6">
            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700/70 dark:bg-slate-900/90">
              <div>
                <h2 class="mb-2 text-base font-semibold text-gray-900 dark:text-slate-100">Description</h2>
                <p class="whitespace-pre-line text-sm text-gray-600 dark:text-slate-300">{{ project.description }}</p>
              </div>
              <div>
                <h2 class="mb-2 text-base font-semibold text-gray-900 dark:text-slate-100">Requirements</h2>
                <p class="whitespace-pre-line text-sm text-gray-600 dark:text-slate-300">{{ project.requirements }}</p>
              </div>
            </div>

            <!-- Applications for company -->
            <div v-if="auth.isCompany && project.company?.user_id === auth.user?.id">
              <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-slate-100">
                Applications
                <span class="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                  {{ applicationStore.applications.length }}
                </span>
              </h2>
              <div v-if="applicationStore.loading" class="space-y-3">
                <div v-for="n in 3" :key="n" class="h-20 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
              </div>
              <div
                v-else-if="applicationStore.applications.length === 0"
                class="py-6 text-center text-sm text-gray-500 dark:text-slate-400"
              >
                No applications yet.
              </div>
              <div v-else class="space-y-3">
                <ApplicationCard
                  v-for="app in applicationStore.applications"
                  :key="app.id"
                  :application="app"
                >
                  <template #actions>
                    <div class="flex gap-2 mt-3" v-if="app.status === 'pending'">
                      <BaseButton
                        variant="primary"
                        size="sm"
                        @click="updateStatus(app.id, 'accepted')"
                      >
                        Accept
                      </BaseButton>
                      <BaseButton
                        variant="danger"
                        size="sm"
                        @click="updateStatus(app.id, 'rejected')"
                      >
                        Reject
                      </BaseButton>
                    </div>
                  </template>
                </ApplicationCard>
              </div>
            </div>
          </div>

          <ProjectDetailSidebar
            :project="project"
            :formatted-deadline="formatDate(project.deadline ?? '')"
          />
        </div>
      </div>

      <ProjectApplyModal
        v-model:show="showApplyModal"
        v-model:cover-letter="coverLetter"
        :submitting="applying"
        :error-message="applyError"
        @submit="submitApplication"
      />
    </template>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { useApplicationStore } from '@/stores/application'
import AppLayout from '@/layouts/AppLayout.vue'
import ApplicationCard from '@/components/applications/ApplicationCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProjectDetailHeader from '@/components/projects/ProjectDetailHeader.vue'
import ProjectDetailSidebar from '@/components/projects/ProjectDetailSidebar.vue'
import ProjectApplyModal from '@/components/projects/ProjectApplyModal.vue'

interface ProjectOwnerCompany {
  user_id?: number
  name?: string
}

interface ProjectDetail {
  id: number
  title?: string
  status?: string
  description?: string
  requirements?: string
  deadline?: string
  max_students?: number
  applications_count?: number
  company?: ProjectOwnerCompany
  tech_stack?: string[]
}

interface ProjectApplication {
  id: number
  project_id?: number
  status?: string
}

export default defineComponent({
  name: 'ProjectDetailView',
  components: {
    AppLayout,
    ApplicationCard,
    BaseButton,
    BaseAlert,
    ProjectDetailHeader,
    ProjectDetailSidebar,
    ProjectApplyModal,
  },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
      applicationStore: useApplicationStore(),
    }
  },
  data() {
    return {
      loading: true,
      fetchError: '',
      deleting: false,
      showApplyModal: false,
      coverLetter: '',
      applying: false,
      applyError: '',
    }
  },
  computed: {
    project(): ProjectDetail | null {
      return this.projectStore.currentProject as ProjectDetail | null
    },
    hasApplied(): boolean {
      return this.applicationStore.applications.some(
        (a) => (a as ProjectApplication).project_id === this.project?.id,
      )
    },
  },
  async mounted() {
    const id = Number(this.$route.params.id)
    try {
      await this.projectStore.fetchProject(id)
      if (this.auth.isCompany) {
        await this.applicationStore.fetchApplications({ project_id: id })
      }
      if (this.auth.isStudent) {
        await this.applicationStore.fetchApplications()
      }
    } catch {
      this.fetchError = 'Could not load project details.'
    } finally {
      this.loading = false
    }
  },
  methods: {
    formatDate(date: string): string {
      return date
        ? new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
          })
        : '–'
    },
    async confirmDelete() {
      if (!confirm('Are you sure you want to delete this project?')) return
      if (!this.project) return
      this.deleting = true
      try {
        await this.projectStore.deleteProject(this.project.id)
        this.$router.push('/projects')
      } finally {
        this.deleting = false
      }
    },
    async submitApplication() {
      if (!this.project) return
      if (this.coverLetter.trim().length < 50) {
        this.applyError = 'Cover letter must be at least 50 characters.'
        return
      }
      this.applying = true
      this.applyError = ''
      try {
        await this.applicationStore.applyToProject(this.project.id, this.coverLetter)
        this.showApplyModal = false
        this.coverLetter = ''
      } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } }
        this.applyError = err?.response?.data?.message ?? 'Failed to submit application.'
      } finally {
        this.applying = false
      }
    },
    async updateStatus(applicationId: number, status: 'accepted' | 'rejected') {
      await this.applicationStore.updateStatus(applicationId, status)
    },
  },
})
</script>

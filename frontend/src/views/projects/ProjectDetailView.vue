<template>
  <AppLayout>
    <div v-if="loading" class="space-y-4 pt-12 lg:pt-0">
      <div class="h-12 w-full animate-pulse rounded-2xl bg-[#f1edf8] dark:bg-slate-800" />
      <div class="h-80 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
      <div class="h-56 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
    </div>

    <BaseAlert v-else-if="fetchError" type="error" :message="fetchError" class="mt-4" />

    <template v-else-if="project">
      <div class="space-y-8 pt-12 lg:space-y-10 lg:pt-0">
        <section class="overflow-hidden rounded-3xl bg-white p-5 dark:bg-slate-900 sm:p-7 lg:p-10">
          <div class="flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl space-y-5">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-[#e8e3f2] px-3 py-1.5 text-xs font-semibold text-[#4d466b] transition hover:bg-[#ddd7f6] dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="goBack"
              >
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                </svg>
                Back
              </button>

              <div class="flex flex-wrap items-center gap-3">
                <ProjectStatusBadge :status="project.status ?? 'draft'" />
                <RouterLink
                  v-if="project.company?.user_id"
                  :to="`/companies/${project.company.user_id}/profile`"
                  class="text-sm font-medium text-slate-500 transition hover:text-[#4f33d7] dark:text-slate-400 dark:hover:text-indigo-300"
                >
                  {{ project.company?.name ?? 'Unknown company' }}
                </RouterLink>
                <span v-else class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ project.company?.name ?? 'Unknown company' }}</span>
              </div>

              <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl lg:text-5xl">
                {{ project.title }}
              </h1>

              <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">
                {{ project.description }}
              </p>

              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-[#f1edf8] p-3.5 dark:bg-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Posted</p>
                  <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ formatDate(project.posted_at ?? project.created_at ?? '') }}</p>
                </div>
                <div class="rounded-2xl bg-[#f1edf8] p-3.5 dark:bg-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Location</p>
                  <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ project.location || 'Not specified' }}</p>
                </div>
                <div class="rounded-2xl bg-[#f1edf8] p-3.5 dark:bg-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Work mode</p>
                  <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ locationStrategyLabel(project.location_strategy) }}</p>
                </div>
              </div>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 xl:w-auto xl:justify-end">
              <template v-if="isOwnerCompany">
                <RouterLink :to="'/projects/' + project.id + '/edit'">
                  <BaseButton variant="secondary" size="sm">Edit</BaseButton>
                </RouterLink>
                <BaseButton variant="danger" size="sm" :loading="deleting" @click="confirmDelete">
                  Delete
                </BaseButton>
              </template>

              <template v-if="canStudentApply">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full bg-[#3f34a6] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#352b91] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                  @click="openApplyModal"
                  :disabled="hasActiveApplication || isProjectFull"
                >
                  {{ applyButtonLabel }}
                </button>
              </template>
            </div>
          </div>
        </section>

        <section class="grid gap-8 lg:grid-cols-12">
          <div class="space-y-8 lg:col-span-8">
            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900 sm:p-8">
              <h2 class="mb-5 text-2xl font-bold text-slate-900 dark:text-slate-100">About the Project</h2>
              <p class="whitespace-pre-line text-[15px] leading-relaxed text-slate-600 dark:text-slate-300">
                {{ project.description }}
              </p>
            </div>

            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900 sm:p-8">
              <h2 class="mb-5 text-2xl font-bold text-slate-900 dark:text-slate-100">Requirements & Skills</h2>

              <p
                v-if="project.requirements"
                class="whitespace-pre-line text-[15px] leading-relaxed text-slate-600 dark:text-slate-300"
              >
                {{ project.requirements }}
              </p>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">No specific requirements were provided yet.</p>

              <div v-if="project.tech_stack?.length" class="mt-6 flex flex-wrap gap-2">
                <span
                  v-for="(tech, index) in project.tech_stack"
                  :key="tech"
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                  :class="detailTechChipClass(index)"
                >
                  {{ tech }}
                </span>
              </div>
            </div>

            <div v-if="isOwnerCompany" class="rounded-3xl bg-white p-6 dark:bg-slate-900 sm:p-8">
              <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-slate-100">
                Applications
                <span class="ml-2 rounded-full bg-[#e8e3f2] px-2 py-0.5 text-xs text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200">
                  {{ applicationStore.applications.length }}
                </span>
              </h2>
              <div v-if="applicationStore.loading" class="space-y-3">
                <div v-for="n in 3" :key="n" class="h-20 animate-pulse rounded-xl bg-[#f1edf8] dark:bg-slate-800" />
              </div>
              <div
                v-else-if="applicationStore.applications.length === 0"
                class="py-6 text-center text-sm text-gray-500 dark:text-slate-400"
              >
                No applications yet.
              </div>
              <CompanyApplicantsPanel
                v-else
                :selected-project="{ id: project.id, title: project.title ?? 'Project' }"
                :applications="applicationStore.applications"
                :updating-id="updatingId"
                @update-status="handlePanelUpdateStatus"
              />
            </div>
          </div>

          <aside class="space-y-6 lg:col-span-4">
            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900">
              <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">Project Overview</h3>
              <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Company</span>
                  <RouterLink
                    v-if="project.company?.user_id"
                    :to="`/companies/${project.company.user_id}/profile`"
                    class="text-right font-semibold text-slate-900 transition hover:text-[#4f33d7] dark:text-slate-100 dark:hover:text-indigo-300"
                  >
                    {{ project.company?.name ?? 'Unknown' }}
                  </RouterLink>
                  <span v-else class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.company?.name ?? 'Unknown' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Status</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.status ?? 'draft' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Posted</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ formatDate(project.posted_at ?? project.created_at ?? '') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Location</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.location || 'Not specified' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Work mode</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ locationStrategyLabel(project.location_strategy) }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Industry</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.industry || 'Not specified' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Duration</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.internship_duration || 'Not specified' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-slate-500 dark:text-slate-400">Max students</span>
                  <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ project.max_students ?? '-' }}</span>
                </div>
              </div>
            </div>

            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900">
              <h3 class="mb-5 text-lg font-semibold text-slate-900 dark:text-slate-100">Timeline</h3>
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e8e3f2] text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 0a6 6 0 0 1 6 6m-6-6a6 6 0 0 0-6 6m6 9c3.314 0 6-2.686 6-6m-6 6c-3.314 0-6-2.686-6-6m6 0-2.5 2.5M12 15l2.5-2.5" />
                    </svg>
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Project posted</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(project.posted_at ?? project.created_at ?? '') }}</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#f1edf8] text-[#4d466b] dark:bg-slate-800 dark:text-slate-300">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm8 0a3 3 0 1 1 0-6 3 3 0 0 1 0 6ZM3 19a5 5 0 0 1 10 0m8 0a5 5 0 0 0-8-4.33" />
                    </svg>
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Applications in progress</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Current status: {{ project.status ?? 'draft' }}</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#f1edf8] text-[#4d466b] dark:bg-slate-800 dark:text-slate-300">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v12H5z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 20h6M12 16v4" />
                    </svg>
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Selection and start</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Date to be confirmed by company</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900">
              <h3 class="mb-2 text-base font-semibold text-slate-900 dark:text-slate-100">Location</h3>
              <div class="mb-4 overflow-hidden rounded-2xl bg-[#f1edf8] dark:bg-slate-800/70">
                <iframe
                  v-if="mapQuery"
                  :src="mapEmbedUrl"
                  class="h-44 w-full"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Project location map"
                />
                <div
                  v-else
                  class="flex h-44 w-full items-center justify-center text-xs font-medium text-slate-500 dark:text-slate-400"
                >
                  No location available for map preview
                </div>
              </div>
              <p class="text-sm text-slate-600 dark:text-slate-300">{{ project.location || 'Not specified' }}</p>
              <a
                v-if="mapQuery"
                :href="mapExternalUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-flex text-xs font-semibold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200"
              >
                Open in maps
              </a>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Internship place provided by company.</p>
            </div>

            <div class="rounded-3xl bg-white p-6 dark:bg-slate-900">
              <h3 class="mb-2 text-base font-semibold text-slate-900 dark:text-slate-100">Need more info?</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300">Reach out to the company via application and cover letter.</p>
              <button
                v-if="canStudentApply"
                type="button"
                class="mt-4 inline-flex items-center justify-center rounded-full bg-[#3f34a6] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#352b91] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                @click="openApplyModal"
                :disabled="hasActiveApplication || isProjectFull"
              >
                {{ applyButtonLabel }}
              </button>
            </div>
          </aside>
        </section>
      </div>

      <ProjectApplyModal
        v-model:show="showApplyModal"
        v-model:cover-letter="coverLetter"
        :title="applyModalTitle"
        :submit-label="applySubmitLabel"
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
import CompanyApplicantsPanel from '@/components/applications/CompanyApplicantsPanel.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProjectStatusBadge from '@/components/projects/ProjectStatusBadge.vue'
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
  location?: string | null
  location_strategy?: 'remote' | 'onsite' | 'hybrid'
  industry?: string | null
  internship_duration?: string | null
  posted_at?: string
  created_at?: string
  max_students?: number
  applications_count?: number
  accepted_applications_count?: number
  company?: ProjectOwnerCompany
  tech_stack?: string[]
}

interface ProjectApplication {
  id: number
  project_id?: number
  status?: string
  cover_letter?: string
}

export default defineComponent({
  name: 'ProjectDetailView',
  components: {
    AppLayout,
    CompanyApplicantsPanel,
    BaseButton,
    BaseAlert,
    ProjectStatusBadge,
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
      updatingId: null as number | null,
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
    isOwnerCompany(): boolean {
      return this.auth.isCompany && this.project?.company?.user_id === this.auth.user?.id
    },
    canStudentApply(): boolean {
      return this.auth.isStudent && this.project?.status === 'open'
    },
    mapQuery(): string {
      const location = this.project?.location?.trim() ?? ''
      if (location) return location

      return this.project?.company?.name?.trim() ?? ''
    },
    mapEmbedUrl(): string {
      if (!this.mapQuery) return ''
      return `https://www.google.com/maps?q=${encodeURIComponent(this.mapQuery)}&z=13&output=embed`
    },
    mapExternalUrl(): string {
      if (!this.mapQuery) return '#'
      return `https://www.google.com/maps?q=${encodeURIComponent(this.mapQuery)}`
    },
    hasApplied(): boolean {
      return this.applicationStore.applications.some(
        (a) => (a as ProjectApplication).project_id === this.project?.id,
      )
    },
    currentStudentApplication(): ProjectApplication | null {
      return (
        (this.applicationStore.applications.find(
          (a) => (a as ProjectApplication).project_id === this.project?.id,
        ) as ProjectApplication | undefined) ?? null
      )
    },
    hasActiveApplication(): boolean {
      const status = this.currentStudentApplication?.status ?? ''
      return status === 'pending' || status === 'accepted'
    },
    canReapply(): boolean {
      const status = this.currentStudentApplication?.status ?? ''
      return status === 'rejected' || status === 'withdrawn'
    },
    isProjectFull(): boolean {
      const accepted = this.project?.accepted_applications_count ?? 0
      const maxStudents = this.project?.max_students ?? 1
      return accepted >= maxStudents
    },
    applyButtonLabel(): string {
      if (this.isProjectFull) return 'Project full'
      if (this.hasActiveApplication) return 'Applied'
      if (this.currentStudentApplication?.status === 'rejected') return 'Update application'
      if (this.currentStudentApplication?.status === 'withdrawn') return 'Apply again'
      return 'Apply now'
    },
    applyModalTitle(): string {
      if (this.currentStudentApplication?.status === 'rejected') return 'Appeal application'
      if (this.currentStudentApplication?.status === 'withdrawn') return 'Apply again'
      return 'Apply to this project'
    },
    applySubmitLabel(): string {
      if (this.canReapply) return 'Send update'
      return 'Submit application'
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
    detailTechChipClass(index: number): string {
      const classes = [
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
        'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
      ]

      return classes[index % classes.length] || 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
    },
    handlePanelUpdateStatus(payload: { id: number; status: 'accepted' | 'rejected' }) {
      return this.updateStatus(payload.id, payload.status)
    },
    locationStrategyLabel(strategy?: string): string {
      if (strategy === 'onsite') return 'On-site'
      if (strategy === 'hybrid') return 'Hybrid'
      return 'Remote'
    },
    openApplyModal() {
      this.applyError = ''
      if (this.canReapply) {
        this.coverLetter = this.currentStudentApplication?.cover_letter ?? ''
      }
      this.showApplyModal = true
    },
    goBack() {
      if (window.history.length > 1) {
        this.$router.back()
        return
      }
      this.$router.push('/projects')
    },
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
      this.updatingId = applicationId
      try {
        await this.applicationStore.updateStatus(applicationId, status)
      } finally {
        this.updatingId = null
      }
    },
  },
})
</script>

<template>
  <section class="space-y-6">
    <div
      v-if="!selectedProject"
      class="rounded-3xl border border-slate-200/80 bg-white py-20 text-center text-slate-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
    >
      <p class="text-base font-semibold">Select a project to view applicants</p>
    </div>

    <template v-else>
      <div class="rounded-3xl bg-white px-6 py-6 dark:bg-slate-900/90 sm:px-8">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <div class="space-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#66628b] dark:text-slate-400">Applicant management</p>
            <h2 class="text-sm font-semibold text-[#2f2a4b] dark:text-slate-100">{{ selectedProject.title }}</h2>
            <p class="max-w-2xl text-xs text-slate-500 dark:text-slate-400">
              Reviewing {{ applications.length }} candidates for this project.
            </p>
          </div>
          <div class="relative h-22 w-22 shrink-0">
            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
              <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="10" class="text-[#e8e2f7] dark:text-slate-700" />
              <circle
                cx="50"
                cy="50"
                r="40"
                fill="none"
                stroke-width="10"
                stroke-linecap="round"
                class="text-[#4f33d7]"
                :stroke-dasharray="`${reviewedCircle} ${fullCircle}`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-lg font-extrabold text-[#4120cd] dark:text-indigo-300">{{ reviewedPercent }}%</span>
              <span class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Reviewed</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-3 md:flex-row md:items-center">
        <div class="w-full flex-1">
          <input
            v-model.trim="searchQuery"
            type="text"
            placeholder="Search by name, skill, university..."
            class="w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
          />
        </div>
        <div class="flex w-full gap-2 overflow-x-auto pb-1 md:w-auto md:pb-0">
          <button
            v-for="option in quickStatusOptions"
            :key="option.value"
            type="button"
            class="shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition"
            :class="statusTabClass(option.value)"
            @click="activeQuickStatus = option.value"
          >
            {{ option.label }}
          </button>
        </div>
      </div>

      <div
        v-if="visibleApplications.length === 0"
        class="rounded-3xl border border-slate-200/80 bg-white py-20 text-center text-slate-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
      >
        <p class="text-base font-semibold">No applicants match this filter</p>
      </div>

      <div v-else class="rounded-3xl bg-[#efeaf8] p-2.5 dark:bg-slate-900/80">
        <div class="hidden grid-cols-12 px-5 py-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 md:grid dark:text-slate-400">
          <div class="col-span-4">Student profile</div>
          <div class="col-span-3">University</div>
          <div class="col-span-1 text-center">Match</div>
          <div class="col-span-2 text-center">Status</div>
          <div class="col-span-2 text-right">Actions</div>
        </div>

        <div class="space-y-3">
          <article
            v-for="application in pagedApplications"
            :key="application.id"
            class="grid grid-cols-1 items-center gap-4 rounded-2xl border border-transparent bg-white px-5 py-4 transition hover:border-[#d5caf5] hover:shadow-[0_12px_30px_rgba(65,32,205,0.08)] md:grid-cols-12 dark:bg-slate-900/90 dark:hover:border-indigo-500/40"
          >
            <div class="col-span-4 flex items-center gap-3">
              <div class="h-12 w-12 shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                <img v-if="avatarUrl(application)" :src="avatarUrl(application)" alt="Applicant avatar" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-300">
                  {{ initials(application.student?.name) }}
                </div>
              </div>
              <div class="min-w-0">
                <RouterLink
                  v-if="application.student?.id"
                  :to="{
                    name: 'students.profile',
                    params: { id: application.student.id },
                    query: selectedProject?.id ? { project_id: String(selectedProject.id) } : undefined,
                  }"
                  class="truncate text-base font-bold text-slate-900 transition hover:text-[#4f33d7] dark:text-slate-100 dark:hover:text-indigo-300"
                >
                  {{ application.student?.name || 'Unknown applicant' }}
                </RouterLink>
                <p v-else class="truncate text-base font-bold text-slate-900 dark:text-slate-100">{{ application.student?.name || 'Unknown applicant' }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ educationLine(application) }}</p>
                <p class="truncate text-[11px] text-slate-400 dark:text-slate-500">Applied {{ formatDate(application.created_at) }}</p>
              </div>
            </div>

            <div class="col-span-3">
              <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ application.student?.profile?.university || 'Not specified' }}</p>
              <p class="mt-1 line-clamp-1 text-xs text-slate-500 dark:text-slate-400">{{ topSkillsLine(application) }}</p>
            </div>

            <div class="col-span-1 flex justify-start md:justify-center">
              <span class="inline-flex min-w-14 items-center justify-center rounded-lg bg-[#e6ddff] px-2 py-1 text-sm font-extrabold text-[#4321cf] dark:bg-indigo-500/20 dark:text-indigo-300">
                {{ matchScore(application) }}%
              </span>
            </div>

            <div class="col-span-2 flex justify-start md:justify-center">
              <span :class="statusPillClass(application.status)" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.09em]">
                <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(application.status)"></span>
                {{ statusLabel(application.status) }}
              </span>
            </div>

            <div class="col-span-2 flex items-center justify-start gap-2 md:justify-end">
              <RouterLink
                v-if="application.student?.id"
                :to="{
                  name: 'students.profile',
                  params: { id: application.student.id },
                  query: selectedProject?.id ? { project_id: String(selectedProject.id) } : undefined,
                }"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#ebe7f7] text-slate-600 transition hover:bg-[#5a42e5] hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-indigo-500"
                title="View full profile"
              >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </RouterLink>
              <button
                v-if="application.status === 'pending'"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#ebe7f7] text-emerald-600 transition hover:bg-emerald-600 hover:text-white"
                :disabled="updatingId === application.id"
                @click="$emit('update-status', { id: application.id, status: 'accepted' })"
                title="Accept"
              >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <button
                v-if="application.status === 'pending'"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#ebe7f7] text-rose-600 transition hover:bg-rose-600 hover:text-white"
                :disabled="updatingId === application.id"
                @click="$emit('update-status', { id: application.id, status: 'rejected' })"
                title="Reject"
              >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                </svg>
              </button>
            </div>
          </article>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
        <div class="rounded-2xl bg-white px-5 py-4 text-sm text-slate-600 dark:bg-slate-900/90 dark:text-slate-300">
          Showing {{ pageRangeLabel }} of {{ visibleApplications.length }} applicants
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 transition hover:bg-[#e9e2fb] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900/90 dark:text-slate-300"
            :disabled="currentPage === 1"
            @click="currentPage = currentPage - 1"
          >
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
            </svg>
          </button>
          <span class="inline-flex min-w-18 items-center justify-center rounded-xl bg-[#4f33d7] px-4 py-2 text-sm font-bold text-white">
            {{ currentPage }} / {{ totalPages }}
          </span>
          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 transition hover:bg-[#e9e2fb] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900/90 dark:text-slate-300"
            :disabled="currentPage >= totalPages"
            @click="currentPage = currentPage + 1"
          >
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
            </svg>
          </button>
        </div>
      </div>
    </template>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import {
  applicantEducationLine,
  applicantMatchScore,
  applicantStatusDotClass,
  applicantStatusLabel,
  applicantStatusPillClass,
  applicantStatusTabClass,
  applicantTopSkillsLine,
  calculateReviewedPercent,
  filterCompanyApplicants,
  type ApplicantQuickStatus,
  type CompanyApplicantListItem,
} from '@/services/applications/ApplicationsViewService'
import { resolveAssetUrl } from '@/services/core/url'

interface CompanyProject {
  id: number
  title: string
}

interface ApplicationListItem extends CompanyApplicantListItem {
  cover_letter?: string
  [key: string]: unknown
  tasks?: Array<{
    id?: number
    status?: 'todo' | 'in_progress' | 'complete' | null
  }>
  student?: {
    name?: string
    email?: string
    id?: number
    github_url?: string
    avatar_url?: string | null
    profile?: {
      university?: string
      degree?: string
      field_of_study?: string
      skills?: string[]
      interests?: string[]
      bio?: string
      about_me?: string
      projects?: Array<{ tech?: string }>
    }
  }
  project?: {
    tech_stack?: string[]
    requirements?: string
  }
}

export default defineComponent({
  name: 'CompanyApplicantsPanel',
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
  },
  emits: ['update-status'],
  data() {
    return {
      searchQuery: '',
      activeQuickStatus: 'all' as ApplicantQuickStatus,
      currentPage: 1,
      pageSize: 6,
      quickStatusOptions: [
        { value: 'all', label: 'All applicants' },
        { value: 'accepted', label: 'Approved' },
        { value: 'pending', label: 'Pending' },
        { value: 'rejected', label: 'Rejected' },
      ] as Array<{ value: ApplicantQuickStatus; label: string }>,
    }
  },
  computed: {
    visibleApplications(): ApplicationListItem[] {
      return filterCompanyApplicants(this.applications, this.activeQuickStatus, this.searchQuery)
    },
    reviewedPercent(): number {
      return calculateReviewedPercent(this.applications)
    },
    fullCircle(): number {
      return Number((2 * Math.PI * 40).toFixed(1))
    },
    reviewedCircle(): number {
      return Number(((this.reviewedPercent / 100) * this.fullCircle).toFixed(1))
    },
    totalPages(): number {
      return Math.max(1, Math.ceil(this.visibleApplications.length / this.pageSize))
    },
    pagedApplications(): ApplicationListItem[] {
      const start = (this.currentPage - 1) * this.pageSize
      return this.visibleApplications.slice(start, start + this.pageSize)
    },
    pageRangeLabel(): string {
      if (!this.visibleApplications.length) return '0-0'
      const start = (this.currentPage - 1) * this.pageSize + 1
      const end = Math.min(this.currentPage * this.pageSize, this.visibleApplications.length)
      return `${start}-${end}`
    },
  },
  watch: {
    selectedProject: {
      handler() {
        this.searchQuery = ''
        this.activeQuickStatus = 'all'
        this.currentPage = 1
      },
      immediate: false,
    },
    activeQuickStatus() {
      this.currentPage = 1
    },
    searchQuery() {
      this.currentPage = 1
    },
    totalPages(nextPages: number) {
      if (this.currentPage > nextPages) {
        this.currentPage = nextPages
      }
    },
  },
  methods: {
    avatarUrl(application: ApplicationListItem): string {
      return resolveAssetUrl(application.student?.avatar_url)
    },
    initials(name?: string): string {
      const safe = (name ?? 'U').trim()
      return safe
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('')
        .slice(0, 2)
    },
    formatDate(date?: string): string {
      if (!date) return 'Unknown'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
    statusLabel(status?: string): string {
      return applicantStatusLabel(status)
    },
    statusTabClass(status: ApplicantQuickStatus): string {
      return applicantStatusTabClass(this.activeQuickStatus, status)
    },
    statusPillClass(status?: string): string {
      return applicantStatusPillClass(status)
    },
    statusDotClass(status?: string): string {
      return applicantStatusDotClass(status)
    },
    educationLine(application: ApplicationListItem): string {
      return applicantEducationLine(application)
    },
    topSkillsLine(application: ApplicationListItem): string {
      return applicantTopSkillsLine(application)
    },
    matchScore(application: ApplicationListItem): number {
      return applicantMatchScore(application)
    },
  },
})
</script>

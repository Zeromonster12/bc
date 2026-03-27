<template>
  <section class="space-y-6">
    <div
      v-if="!selectedProject"
      class="rounded-3xl border border-slate-200/80 bg-white py-20 text-center text-slate-500 dark:border-slate-700/70 dark:bg-slate-900/90 dark:text-slate-400"
    >
      <p class="text-base font-semibold">Select a project to view applicants</p>
    </div>

    <template v-else>
      <div class="rounded-3xl border border-[#dfd8ef] bg-linear-to-br from-white via-[#f7f3ff] to-[#ece5ff] p-6 dark:border-slate-700/70 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/30">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <div class="space-y-2">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#5a42e5]">Applicant management</p>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">{{ selectedProject.title }}</h2>
            <p class="max-w-2xl text-sm text-slate-600 dark:text-slate-300">
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
        <div class="relative w-full flex-1">
          <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" />
          </svg>
          <input
            v-model.trim="searchQuery"
            type="text"
            placeholder="Search by name, skill, university..."
            class="h-12 w-full rounded-2xl border border-[#d8d0ea] bg-white pl-11 pr-4 text-sm text-slate-800 outline-none transition focus:border-[#5a42e5] focus:ring-2 focus:ring-[#5a42e5]/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
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
                  :to="{ name: 'students.profile', params: { id: application.student.id } }"
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
                :to="{ name: 'students.profile', params: { id: application.student.id } }"
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
import { resolveAssetUrl } from '@/services/core/url'

interface CompanyProject {
  id: number
  title: string
}

interface ApplicationListItem {
  id: number
  cover_letter?: string
  created_at?: string
  status?: string
  tasks?: Array<{
    id?: number
    status?: 'todo' | 'in_progress' | 'complete' | null
  }>
  student?: {
    id?: number
    name?: string
    email?: string
    github_url?: string
    avatar_url?: string | null
    profile?: {
      university?: string
      degree?: string
      field_of_study?: string
      skills?: string[]
      interests?: string[]
      projects?: Array<{ tech?: string }>
      bio?: string
      about_me?: string
    }
  }
  project?: {
    requirements?: string
    tech_stack?: string[]
  }
  [key: string]: unknown
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
    updatingStatus: {
      type: String,
      default: '',
    },
  },
  emits: ['update-status'],
  data() {
    return {
      searchQuery: '',
      activeQuickStatus: 'all' as 'all' | 'pending' | 'accepted' | 'rejected',
      currentPage: 1,
      pageSize: 6,
      quickStatusOptions: [
        { value: 'all', label: 'All applicants' },
        { value: 'accepted', label: 'Approved' },
        { value: 'pending', label: 'Pending' },
        { value: 'rejected', label: 'Rejected' },
      ] as Array<{ value: 'all' | 'pending' | 'accepted' | 'rejected'; label: string }>,
    }
  },
  computed: {
    visibleApplications(): ApplicationListItem[] {
      const query = this.searchQuery.toLowerCase()

      return this.applications.filter((application) => {
        const status = (application.status ?? 'pending') as 'pending' | 'accepted' | 'rejected' | 'withdrawn'
        if (this.activeQuickStatus !== 'all' && status !== this.activeQuickStatus) {
          return false
        }

        if (!query) return true

        const haystack = [
          application.student?.name,
          application.student?.email,
          application.student?.profile?.university,
          application.student?.profile?.degree,
          application.student?.profile?.field_of_study,
          ...(application.student?.profile?.skills ?? []),
          ...(application.student?.profile?.interests ?? []),
          application.student?.profile?.bio,
          application.student?.profile?.about_me,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()

        return haystack.includes(query)
      })
    },
    reviewedPercent(): number {
      if (!this.applications.length) return 0
      const reviewed = this.applications.filter((app) => app.status === 'accepted' || app.status === 'rejected').length
      return Math.round((reviewed / this.applications.length) * 100)
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
      if (status === 'accepted') return 'Approved'
      if (status === 'rejected') return 'Rejected'
      if (status === 'withdrawn') return 'Withdrawn'
      return 'Pending'
    },
    statusTabClass(status: 'all' | 'pending' | 'accepted' | 'rejected'): string {
      if (this.activeQuickStatus === status) {
        return 'bg-[#d8cdff] text-[#3f1ccc] dark:bg-indigo-500/25 dark:text-indigo-300'
      }

      return 'bg-white text-slate-600 hover:bg-[#ece6fb] dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
    },
    statusPillClass(status?: string): string {
      if (status === 'accepted') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
      if (status === 'rejected') return 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
      if (status === 'withdrawn') return 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
      return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    },
    statusDotClass(status?: string): string {
      if (status === 'accepted') return 'bg-emerald-500'
      if (status === 'rejected') return 'bg-rose-500'
      if (status === 'withdrawn') return 'bg-slate-500'
      return 'bg-amber-500'
    },
    educationLine(application: ApplicationListItem): string {
      const degree = application.student?.profile?.degree?.trim() ?? ''
      const field = application.student?.profile?.field_of_study?.trim() ?? ''
      const pieces = [degree, field].filter(Boolean)
      return pieces.length ? pieces.join(' - ') : 'Education not specified'
    },
    topSkillsLine(application: ApplicationListItem): string {
      const skills = application.student?.profile?.skills ?? []
      if (!skills.length) return 'Skills not specified'
      return skills.slice(0, 3).join(', ')
    },
    normalizeChip(value: string): string {
      return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '')
        .replace(/[^a-z0-9+#]/g, '')
    },
    studentSkillChips(application: ApplicationListItem): Set<string> {
      const chips = application.student?.profile?.skills ?? []
      return new Set(
        chips
          .map((chip) => this.normalizeChip(chip))
          .filter((chip) => chip.length > 0),
      )
    },
    projectTechChips(application: ApplicationListItem): Set<string> {
      const chips = application.project?.tech_stack ?? []
      return new Set(
        chips
          .map((chip) => this.normalizeChip(chip))
          .filter((chip) => chip.length > 0),
      )
    },
    matchScore(application: ApplicationListItem): number {
      const projectChips = this.projectTechChips(application)
      const studentChips = this.studentSkillChips(application)

      if (!projectChips.size) {
        return 0
      }

      let overlap = 0
      projectChips.forEach((chip) => {
        if (studentChips.has(chip)) {
          overlap += 1
        }
      })

      const ratio = overlap / projectChips.size
      const score = Math.round(Math.max(0, Math.min(100, ratio * 100)))
      return score
    },
    taskStatsFor(application: ApplicationListItem): {
      total: number
      todo: number
      inProgress: number
      complete: number
    } {
      const tasks = application.tasks ?? []
      return {
        total: tasks.length,
        todo: tasks.filter((task) => task.status === 'todo').length,
        inProgress: tasks.filter((task) => task.status === 'in_progress').length,
        complete: tasks.filter((task) => task.status === 'complete').length,
      }
    },
  },
})
</script>

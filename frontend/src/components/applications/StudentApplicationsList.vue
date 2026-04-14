<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-2xl font-extrabold tracking-tight text-[#1f1a38] dark:text-slate-100 sm:text-3xl">Application Journey</h1>
      <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#6c6786] dark:text-slate-300">
        Track your progress across applications and keep every opportunity in one clear view.
      </p>
    </header>

    <div class="flex flex-wrap items-center gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        :class="[
          'rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[0.08em] transition sm:px-5 sm:text-xs',
          activeTab === tab.value
            ? 'bg-[#cdc2ff] text-[#35207f] dark:bg-indigo-500/25 dark:text-indigo-200'
            : 'bg-[#ece8f4] text-[#66607d] hover:bg-[#dfd8ef] dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
        ]"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="visibleApplications.length === 0" class="rounded-2xl border border-dashed border-[#d6d1e7] bg-[#f7f4fc] p-8 text-center dark:border-slate-700 dark:bg-slate-900/70">
      <p class="text-base font-semibold text-[#3a3558] dark:text-slate-200">No applications in this section yet.</p>
      <RouterLink
        to="/projects"
        class="mt-4 inline-flex rounded-full bg-[#2f2952] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#241f42] dark:bg-indigo-600 dark:hover:bg-indigo-500"
      >
        Browse opportunities
      </RouterLink>
    </div>

    <div v-else class="rounded-3xl bg-[#f1edf8] p-2.5 dark:bg-slate-900/80">
      <div class="hidden grid-cols-[minmax(0,1.45fr)_130px_170px_170px] items-center gap-3 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.08em] text-[#5b5676] md:grid dark:text-slate-400">
        <div>Application</div>
        <div>Applied</div>
        <div>Status</div>
        <div class="text-right">Actions</div>
      </div>

      <div class="space-y-2.5">
        <article
          v-for="application in visibleApplications"
          :key="application.id"
          class="grid grid-cols-1 gap-3 rounded-2xl bg-white px-4 py-3.5 md:grid-cols-[minmax(0,1.45fr)_130px_170px_170px] md:items-center dark:bg-slate-900"
        >
          <div class="flex min-w-0 items-start gap-3">
            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8e3f2] text-[11px] font-bold text-[#4d466b] dark:bg-slate-800 dark:text-slate-200">
              <img
                v-if="companyLogoUrl(application)"
                :src="companyLogoUrl(application)"
                :alt="`${companyName(application)} logo`"
                class="h-full w-full object-cover"
                loading="lazy"
              >
              <span v-else>{{ companyInitials(application) }}</span>
            </div>

            <div class="min-w-0">
              <p class="truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-[#7c7699] dark:text-slate-400">
                <RouterLink
                  v-if="companyProfilePath(application)"
                  :to="companyProfilePath(application) as string"
                  class="transition hover:text-[#4f33d7] dark:hover:text-indigo-300"
                >
                  {{ companyName(application) }}
                </RouterLink>
                <span v-else>{{ companyName(application) }}</span>
              </p>
              <h3 class="mt-1 truncate text-sm font-bold text-[#1f1a38] dark:text-slate-100">
                {{ projectTitle(application) }}
              </h3>
              <p v-if="coverLetterPreview(application)" class="mt-1 line-clamp-1 text-xs text-[#66607d] dark:text-slate-300">
                {{ coverLetterPreview(application) }}
              </p>
            </div>
          </div>

          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-[#8b86a3] md:hidden dark:text-slate-500">Applied</p>
            <p class="text-xs font-semibold text-[#3a3558] dark:text-slate-200">{{ formatDate(application.created_at) }}</p>
          </div>

          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-[#8b86a3] md:hidden dark:text-slate-500">Status</p>
            <span
              class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em]"
              :class="statusPillClass(application.status)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(application.status)" />
              {{ statusLabel(application.status) }}
            </span>
            <p class="mt-1 text-[11px] text-[#6f6989] dark:text-slate-400">{{ statusHint(application.status) }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-2 md:justify-end">
            <RouterLink
              v-if="application.project_id"
              :to="`/projects/${application.project_id}`"
              class="inline-flex rounded-full bg-[#3f34a6] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#352b91] dark:bg-indigo-600 dark:hover:bg-indigo-500"
            >
              View details
            </RouterLink>
            <button
              v-if="application.status === 'pending'"
              type="button"
              class="inline-flex rounded-full bg-[#e8e3f2] px-3.5 py-1.5 text-xs font-semibold text-[#5c5579] transition hover:bg-[#ddd7f6] hover:text-[#b42323] dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-rose-300"
              :disabled="withdrawingId === application.id"
              @click="$emit('withdraw', application.id)"
            >
              {{ withdrawingId === application.id ? 'Withdrawing...' : 'Withdraw' }}
            </button>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import {
  STUDENT_APPLICATION_TABS,
  companyName,
  companyProfilePath,
  coverLetterPreview,
  filterStudentApplicationsByTab,
  projectTitle,
  statusHint,
  statusLabel,
  type StudentApplicationListItem,
  type StudentTab,
} from '@/services/applications/StudentApplicationsService'
import { resolveAssetUrl } from '@/services/core/url'

export default defineComponent({
  name: 'StudentApplicationsList',
  props: {
    applications: {
      type: Array as PropType<StudentApplicationListItem[]>,
      required: true,
    },
    withdrawingId: {
      type: Number as PropType<number | null>,
      default: null,
    },
  },
  emits: ['withdraw'],
  data() {
    return {
      activeTab: 'active' as StudentTab,
      tabs: STUDENT_APPLICATION_TABS,
    }
  },
  computed: {
    visibleApplications(): StudentApplicationListItem[] {
      return filterStudentApplicationsByTab(this.applications, this.activeTab)
    },
  },
  methods: {
    projectTitle,
    companyName,
    companyProfilePath,
    coverLetterPreview,
    companyInitials(application: StudentApplicationListItem): string {
      const name = this.companyName(application)
      return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || 'CO'
    },
    companyLogoUrl(application: StudentApplicationListItem): string {
      const company = (application.project?.company ?? {}) as Record<string, unknown>
      const profile = (company.profile ?? {}) as Record<string, unknown>

      const possibleUrl = [
        company.logo_url,
        company.avatar_url,
        company.logo,
        company.avatar,
        profile.logo_url,
        profile.avatar_url,
      ].find((value) => typeof value === 'string' && value.trim().length > 0)

      return resolveAssetUrl(typeof possibleUrl === 'string' ? possibleUrl : '')
    },
    statusLabel,
    statusHint,
    statusPillClass(status?: string): string {
      if (status === 'accepted') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
      }
      if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
      }
      if (status === 'withdrawn') {
        return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
      }

      return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    },
    statusDotClass(status?: string): string {
      if (status === 'accepted') {
        return 'bg-emerald-500 dark:bg-emerald-300'
      }
      if (status === 'rejected') {
        return 'bg-rose-500 dark:bg-rose-300'
      }
      if (status === 'withdrawn') {
        return 'bg-slate-500 dark:bg-slate-300'
      }

      return 'bg-amber-500 dark:bg-amber-300'
    },
    formatDate(value?: string): string {
      if (!value) return 'Unknown'
      return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
  },
})
</script>

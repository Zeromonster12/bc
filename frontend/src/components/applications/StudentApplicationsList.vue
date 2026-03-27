<template>
  <div class="space-y-6">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-[#1f1a38] sm:text-3xl">Application Journey</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#6c6786]">
          Sleduj svoj progress napriec prihlaskami a maj prehlad o kazdej prilezitosti na jednom mieste.
        </p>
      </div>

      <div class="flex items-center gap-3 rounded-xl border border-[#ddd7ea] bg-[#f4f1fb] px-3 py-2.5">
        <div class="relative flex h-11 w-11 items-center justify-center">
          <svg class="h-full w-full -rotate-90" viewBox="0 0 44 44" fill="none">
            <circle cx="22" cy="22" r="18" stroke="#d9d2ec" stroke-width="4" />
            <circle
              cx="22"
              cy="22"
              r="18"
              stroke="#4d33c5"
              stroke-width="4"
              stroke-linecap="round"
              :stroke-dasharray="`${profileStrengthCircumference} ${profileStrengthCircumference}`"
              :stroke-dashoffset="profileStrengthOffset"
            />
          </svg>
          <span class="absolute text-[9px] font-extrabold text-[#2f2952]">{{ profileStrength }}%</span>
        </div>
        <div>
          <p class="text-[9px] font-semibold uppercase tracking-[0.12em] text-[#7c7699]">Profile strength</p>
          <p class="mt-0.5 text-xs font-bold text-[#2f2952]">{{ profileStrengthLabel }}</p>
        </div>
      </div>
    </header>

    <div class="flex flex-wrap items-center gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        :class="[
          'rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[0.08em] transition sm:px-5 sm:text-xs',
          activeTab === tab.value
            ? 'bg-[#cdc2ff] text-[#35207f]'
            : 'bg-[#ece8f4] text-[#66607d] hover:bg-[#dfd8ef]',
        ]"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="visibleApplications.length === 0" class="rounded-2xl border border-dashed border-[#d6d1e7] bg-[#f7f4fc] p-8 text-center">
      <p class="text-base font-semibold text-[#3a3558]">No applications in this section yet.</p>
      <RouterLink
        to="/projects"
        class="mt-4 inline-flex rounded-full bg-[#2f2952] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#241f42]"
      >
        Browse opportunities
      </RouterLink>
    </div>

    <div v-else class="grid grid-cols-1 gap-4 xl:grid-cols-2">
      <article
        v-for="application in visibleApplications"
        :key="application.id"
        class="rounded-3xl border border-[#dfd9ec] bg-white p-5 shadow-[0_8px_22px_rgba(31,26,56,0.06)] transition hover:shadow-[0_12px_30px_rgba(31,26,56,0.1)]"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[#7c7699]">
              <RouterLink
                v-if="companyProfilePath(application)"
                :to="companyProfilePath(application) as string"
                class="transition hover:text-[#4f33d7]"
              >
                {{ companyName(application) }}
              </RouterLink>
              <span v-else>{{ companyName(application) }}</span>
            </p>
            <h3 class="mt-1.5 text-lg font-extrabold tracking-tight text-[#1f1a38]">
              {{ projectTitle(application) }}
            </h3>
          </div>
          <span :class="statusBadgeClass(application.status)">
            {{ statusLabel(application.status) }}
          </span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[#8b86a3]">Applied date</p>
            <p class="mt-1 text-sm font-bold text-[#2f2952]">{{ formatDate(application.created_at) }}</p>
          </div>
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[#8b86a3]">Status</p>
            <p class="mt-1 text-sm font-bold text-[#2f2952]">{{ statusHint(application.status) }}</p>
          </div>
        </div>

        <p v-if="coverLetterPreview(application)" class="mt-4 line-clamp-2 text-sm leading-relaxed text-[#66607d]">
          {{ coverLetterPreview(application) }}
        </p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
          <RouterLink
            v-if="application.project_id"
            :to="`/projects/${application.project_id}`"
            class="inline-flex rounded-full bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
          >
            View details
          </RouterLink>
          <button
            v-if="application.status === 'pending'"
            type="button"
            class="inline-flex rounded-full border border-[#d8d2e7] px-4 py-2 text-sm font-semibold text-[#6a647f] transition hover:border-[#cf4a4a] hover:text-[#b42323]"
            :disabled="withdrawingId === application.id"
            @click="$emit('withdraw', application.id)"
          >
            {{ withdrawingId === application.id ? 'Withdrawing...' : 'Withdraw' }}
          </button>
        </div>
      </article>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import {
  STUDENT_APPLICATION_TABS,
  calculateProfileStrength,
  companyName,
  companyProfilePath,
  coverLetterPreview,
  filterStudentApplicationsByTab,
  profileStrengthLabel,
  projectTitle,
  statusBadgeClass,
  statusHint,
  statusLabel,
  type StudentApplicationListItem,
  type StudentTab,
} from '@/services/applications/StudentApplicationsService'

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
    profileStrength(): number {
      return calculateProfileStrength(this.applications)
    },
    profileStrengthLabel(): string {
      return profileStrengthLabel(this.profileStrength)
    },
    profileStrengthCircumference(): number {
      return 2 * Math.PI * 18
    },
    profileStrengthOffset(): number {
      const progress = this.profileStrength / 100
      return this.profileStrengthCircumference * (1 - progress)
    },
  },
  methods: {
    projectTitle,
    companyName,
    companyProfilePath,
    coverLetterPreview,
    statusLabel,
    statusHint,
    statusBadgeClass,
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

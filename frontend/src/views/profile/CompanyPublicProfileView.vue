<template>
  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-5">
      <BaseAlert v-if="errorMessage" type="error" :message="errorMessage" class="mt-2" />

      <div v-if="loading" class="space-y-4">
        <div class="h-20 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
        <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
      </div>

      <template v-else>
        <section class="overflow-hidden rounded-3xl bg-white p-6 dark:bg-slate-900">
          <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-4">
              <div class="h-18 w-18 shrink-0 overflow-hidden rounded-2xl bg-[#f1edf8] dark:bg-slate-800">
                <img v-if="logoUrl" :src="logoUrl" alt="Company logo" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-lg font-bold text-slate-500 dark:text-slate-300">
                  {{ initials }}
                </div>
              </div>
              <div class="min-w-0">
                <p class="truncate text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">{{ companyName }}</p>
                <p class="truncate text-sm text-slate-600 dark:text-slate-300">{{ fieldOrFallback(form.tagline) }}</p>
                <p class="mt-1 truncate text-sm text-[#4f33d7] dark:text-indigo-300">{{ fieldOrFallback(form.industry) }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <a
                v-if="form.website"
                :href="form.website"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center rounded-full bg-[#3f34a6] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#352b91] dark:bg-indigo-600 dark:hover:bg-indigo-500"
              >
                Website
              </a>
              <RouterLink
                to="/projects"
                class="inline-flex items-center rounded-full bg-[#e8e3f2] px-4 py-2 text-sm font-semibold text-[#4d466b] transition hover:bg-[#ddd7f6] dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
              >
                Back to projects
              </RouterLink>
            </div>
          </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">About company</h2>
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ fieldOrFallback(form.description) }}</p>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Core info</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Industry</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.industry) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Company size</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.company_size) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Founded year</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.founded_year) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Remote policy</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.remote_policy) }}</dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Headquarters</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">City</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.headquarters_city || form.billing_city) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Country</dt>
                <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.headquarters_country) }}</dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Hiring and tech</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hiring focus</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ fieldOrFallback(form.hiring_focus) }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tech stack</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ fieldOrFallback(form.tech_stack) }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Contacts and links</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div class="space-y-2 text-sm">
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Contact person:</span> {{ fieldOrFallback(form.contact_person_full_name) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Contact email:</span> {{ fieldOrFallback(form.contact_email || profileUser.email) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Contact phone:</span> {{ fieldOrFallback(form.contact_phone) }}</p>
              </div>
              <div class="space-y-2 text-sm">
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Website:</span> {{ fieldOrFallback(form.website) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Careers:</span> {{ fieldOrFallback(form.careers_url) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">LinkedIn:</span> {{ fieldOrFallback(form.linkedin_url) }}</p>
              </div>
            </div>
          </article>
        </section>
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProfileService from '@/services/profile/ProfileService'
import {
  createDefaultCompanyProfileForm,
  hydrateCompanyProfileForm,
  type CompanyProfileForm,
} from '@/services/profile/CompanyProfileFormService'
import { resolveAssetUrl } from '@/services/core/url'

interface PublicCompanyUser {
  id?: number
  name?: string
  email?: string
}

export default defineComponent({
  name: 'CompanyPublicProfileView',
  components: { AppLayout, BaseAlert },
  data() {
    return {
      loading: true,
      errorMessage: '',
      form: createDefaultCompanyProfileForm() as CompanyProfileForm,
      profileUser: {} as PublicCompanyUser,
      logoUrl: '',
    }
  },
  computed: {
    companyName(): string {
      const business = String(this.form.business_name ?? '').trim()
      if (business) return business
      const name = String(this.form.name ?? '').trim()
      if (name) return name
      return String(this.profileUser.name ?? 'Company profile').trim()
    },
    initials(): string {
      return this.companyName
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('')
        .slice(0, 2) || 'C'
    },
  },
  async mounted() {
    const userId = Number(this.$route.params.id)

    if (!Number.isFinite(userId) || userId <= 0) {
      this.errorMessage = 'Invalid company profile link.'
      this.loading = false
      return
    }

    try {
      const response = await ProfileService.getCompanyProfileById(userId)
      const payload = (response?.data ?? {}) as {
        user?: PublicCompanyUser
        profile?: Record<string, unknown>
      }

      this.profileUser = payload.user ?? {}
      this.form = hydrateCompanyProfileForm(payload.profile ?? {})

      const logoCandidate = payload.profile?.logo_url
      this.logoUrl = typeof logoCandidate === 'string' ? resolveAssetUrl(logoCandidate) : ''
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      this.errorMessage = err?.response?.data?.message ?? 'Failed to load company profile.'
    } finally {
      this.loading = false
    }
  },
  methods: {
    fieldOrFallback(value: unknown): string {
      const normalized = String(value ?? '').trim()
      return normalized.length ? normalized : 'Not provided'
    },
  },
})
</script>

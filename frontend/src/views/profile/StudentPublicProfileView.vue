<template>
  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-6">
      <BaseAlert v-if="errorMessage" type="error" :message="errorMessage" class="mt-2" />

      <div v-if="loading" class="space-y-4">
        <div class="h-20 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
        <div v-for="n in 5" :key="n" class="h-32 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
      </div>

      <template v-else>
        <section class="overflow-hidden rounded-3xl bg-white p-6 dark:bg-slate-900">
          <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-4">
              <div class="h-18 w-18 shrink-0 overflow-hidden rounded-2xl bg-[#f1edf8] dark:bg-slate-800">
                <img v-if="avatarUrl" :src="avatarUrl" alt="Student avatar" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-lg font-bold text-slate-500 dark:text-slate-300">
                  {{ initials }}
                </div>
              </div>
              <div class="min-w-0">
                <p class="truncate text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">{{ profileUser.name || 'Student profile' }}</p>
                <p class="truncate text-sm text-slate-600 dark:text-slate-300">{{ profileUser.email || 'Email not available' }}</p>
                <p class="mt-1 truncate text-sm text-[#4f33d7] dark:text-indigo-300">{{ fieldOrFallback(form.headline) }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <a
                v-if="github.profile_url"
                :href="github.profile_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center rounded-full bg-[#3f34a6] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#352b91] dark:bg-indigo-600 dark:hover:bg-indigo-500"
              >
                GitHub {{ github.username ? `@${github.username}` : '' }}
              </a>
              <RouterLink
                to="/applications"
                class="inline-flex items-center rounded-full bg-[#e8e3f2] px-4 py-2 text-sm font-semibold text-[#4d466b] transition hover:bg-[#ddd7f6] dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
              >
                Back to applications
              </RouterLink>
            </div>
          </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Personal details</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Phone</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.phone) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Alternate email</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.alternate_email) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Date of birth</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.date_of_birth) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Gender</dt>
                <dd class="font-medium capitalize text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.gender) }}</dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Location</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Country</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.country) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">City</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.city) }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-slate-500 dark:text-slate-400">Address</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.address_line) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Postal code</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.postal_code) }}</dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Education</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500 dark:text-slate-400">University</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.university) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Faculty</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.faculty) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Degree</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.degree) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Field of study</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.field_of_study) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Year of study</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(formatNumeric(form.year_of_study)) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Graduation year</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(formatNumeric(form.graduation_year)) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">GPA</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.gpa) }}</dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Work preferences</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Availability</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.availability) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Preferred work type</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.preferred_work_type) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Expected salary min</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.expected_salary_min) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Expected salary max</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.expected_salary_max) }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-slate-500 dark:text-slate-400">Preferred locations</dt>
                <dd class="mt-1 flex flex-wrap gap-2">
                  <span v-for="item in form.preferred_locations" :key="item" class="rounded-full bg-[#e8e3f2] px-3 py-1 text-xs font-semibold text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200">{{ item }}</span>
                  <span v-if="form.preferred_locations.length === 0" class="text-sm font-medium text-slate-800 dark:text-slate-200">Not provided</span>
                </dd>
              </div>
            </dl>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">About</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Short bio</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ fieldOrFallback(form.bio) }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Detailed introduction</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ fieldOrFallback(form.about_me) }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Skills and interests</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Skills</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span v-for="item in form.skills" :key="item" class="rounded-full bg-[#e8e3f2] px-3 py-1 text-xs font-semibold text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200">{{ item }}</span>
                  <span v-if="form.skills.length === 0" class="text-sm font-medium text-slate-800 dark:text-slate-200">Not provided</span>
                </div>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Interests</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span v-for="item in form.interests" :key="item" class="rounded-full bg-[#e8e3f2] px-3 py-1 text-xs font-semibold text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200">{{ item }}</span>
                  <span v-if="form.interests.length === 0" class="text-sm font-medium text-slate-800 dark:text-slate-200">Not provided</span>
                </div>
              </div>
            </div>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Links and projects</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div class="space-y-2 text-sm">
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Portfolio:</span> {{ fieldOrFallback(form.portfolio_url) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">CV URL:</span> {{ fieldOrFallback(form.cv_url) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">LinkedIn:</span> {{ fieldOrFallback(form.linkedin_url) }}</p>
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">Website:</span> {{ fieldOrFallback(form.website_url) }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Projects</p>
                <ul v-if="form.projects.length" class="mt-2 space-y-2">
                  <li v-for="(item, index) in form.projects" :key="`${item.title}-${index}`" class="rounded-2xl bg-[#f1edf8] p-3 dark:bg-slate-800">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ fieldOrFallback(item.title) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ fieldOrFallback(item.tech) }}</p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ fieldOrFallback(item.description) }}</p>
                  </li>
                </ul>
                <p v-else class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-200">No projects listed</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Languages</h2>
            <ul v-if="form.languages.length" class="mt-4 space-y-2 text-sm">
              <li v-for="item in form.languages" :key="`${item.name}-${item.level}`" class="flex items-center justify-between rounded-2xl bg-[#f1edf8] px-3 py-2 dark:bg-slate-800">
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ item.name }}</span>
                <span class="rounded-full bg-[#e8e3f2] px-2.5 py-0.5 text-xs font-semibold text-[#4d466b] dark:bg-slate-700 dark:text-slate-200">{{ item.level || 'n/a' }}</span>
              </li>
            </ul>
            <p v-else class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">No languages listed</p>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Certifications</h2>
            <ul v-if="form.certifications.length" class="mt-4 space-y-2 text-sm">
              <li v-for="(item, index) in form.certifications" :key="`${item.name}-${index}`" class="rounded-2xl bg-[#f1edf8] px-3 py-2 dark:bg-slate-800">
                <p class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(item.name) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ fieldOrFallback(item.issuer) }}{{ item.year ? ` - ${item.year}` : '' }}</p>
              </li>
            </ul>
            <p v-else class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">No certifications listed</p>
          </article>

          <article class="rounded-3xl bg-white p-6 dark:bg-slate-900 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Emergency contact</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Name</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.emergency_contact_name) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500 dark:text-slate-400">Phone</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ fieldOrFallback(form.emergency_contact_phone) }}</dd>
              </div>
            </dl>
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
  createDefaultStudentProfileForm,
  hydrateStudentProfileForm,
  type StudentProfileForm,
} from '@/services/profile/StudentProfileFormService'
import { resolveAssetUrl } from '@/services/core/url'

interface PublicProfileUser {
  id?: number
  name?: string
  email?: string
  avatar_url?: string | null
}

interface PublicGitHubInfo {
  connected?: boolean
  username?: string
  profile_url?: string
  avatar_url?: string
}

export default defineComponent({
  name: 'StudentPublicProfileView',
  components: { AppLayout, BaseAlert },
  data() {
    return {
      loading: true,
      errorMessage: '',
      form: createDefaultStudentProfileForm() as StudentProfileForm,
      profileUser: {} as PublicProfileUser,
      github: {} as PublicGitHubInfo,
    }
  },
  computed: {
    avatarUrl(): string {
      return resolveAssetUrl(this.profileUser.avatar_url)
    },
    initials(): string {
      const value = (this.profileUser.name ?? 'U').trim()
      return value
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('')
        .slice(0, 2)
    },
  },
  async mounted() {
    const userId = Number(this.$route.params.id)

    if (!Number.isFinite(userId) || userId <= 0) {
      this.errorMessage = 'Invalid student profile link.'
      this.loading = false
      return
    }

    try {
      const response = await ProfileService.getStudentProfileById(userId)
      const payload = (response?.data ?? {}) as {
        user?: PublicProfileUser
        github?: PublicGitHubInfo
        profile?: Record<string, unknown>
      }

      this.profileUser = payload.user ?? {}
      this.github = payload.github ?? {}
      this.form = hydrateStudentProfileForm(payload.profile ?? {})
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      this.errorMessage = err?.response?.data?.message ?? 'Failed to load student profile.'
    } finally {
      this.loading = false
    }
  },
  methods: {
    fieldOrFallback(value: unknown): string {
      const normalized = String(value ?? '').trim()
      return normalized.length ? normalized : 'Not provided'
    },
    formatNumeric(value: number | '' | undefined): string {
      if (value === '' || value === undefined || value === null) return ''
      return String(value)
    },
  },
})
</script>

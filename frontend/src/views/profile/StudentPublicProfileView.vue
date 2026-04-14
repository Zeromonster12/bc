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
                <p class="mt-1 truncate text-sm text-[#4f33d7] dark:text-indigo-300">{{ fieldOrFallback(form.headline) }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <a
                v-if="githubUrl"
                :href="githubUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center rounded-full bg-[#3f34a6] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#352b91] dark:bg-indigo-600 dark:hover:bg-indigo-500"
              >
                Linked GitHub
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
                <p><span class="font-semibold text-slate-700 dark:text-slate-200">GitHub:</span> {{ fieldOrFallback(form.github_url) }}</p>
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

            <div class="mt-5 rounded-2xl bg-[#f1edf8] p-4 dark:bg-slate-800/70">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Uploaded CV documents</p>

              <div v-if="cvLoading" class="mt-3 space-y-2">
                <div v-for="n in 2" :key="n" class="h-10 animate-pulse rounded-xl bg-white/80 dark:bg-slate-700"></div>
              </div>

              <p v-else-if="cvFiles.length === 0" class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                No downloadable CV file available.
              </p>

              <ul v-else class="mt-3 space-y-2">
                <li
                  v-for="cv in cvFiles"
                  :key="cv.id"
                  class="flex flex-col gap-2 rounded-xl bg-white/90 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between dark:bg-slate-900"
                >
                  <div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ cv.original_filename }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      {{ humanFileSize(cv.size_bytes) }} - Uploaded {{ formatDate(cv.uploaded_at) }}
                    </p>
                  </div>

                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full bg-[#3f34a6] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#352b91] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    :disabled="!canDownloadCv(cv.scan_status) || downloadingCvId === cv.id"
                    @click="handleCvDownload(cv)"
                  >
                    {{ downloadingCvId === cv.id ? 'Downloading...' : 'Download CV' }}
                  </button>
                </li>
              </ul>
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
        </section>
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProfileService, { type StudentCvFileItem } from '@/services/profile/ProfileService'
import {
  createDefaultStudentProfileForm,
  hydrateStudentProfileForm,
  type StudentProfileForm,
} from '@/services/profile/StudentProfileFormService'
import { resolveAssetUrl } from '@/services/core/url'

interface PublicProfileUser {
  id?: number
  name?: string
  avatar_url?: string | null
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
      cvLoading: false,
      cvFiles: [] as StudentCvFileItem[],
      downloadingCvId: null as number | null,
      projectId: null as number | null,
    }
  },
  computed: {
    avatarUrl(): string {
      return resolveAssetUrl(this.profileUser.avatar_url)
    },
    githubUrl(): string {
      const value = String(this.form.github_url ?? '').trim()
      if (!value) return ''
      return /^https?:\/\//i.test(value) ? value : ''
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
    const projectIdRaw = Number(this.$route.query.project_id)
    this.projectId = Number.isFinite(projectIdRaw) && projectIdRaw > 0 ? projectIdRaw : null

    if (!Number.isFinite(userId) || userId <= 0) {
      this.errorMessage = 'Invalid student profile link.'
      this.loading = false
      return
    }

    try {
      const response = await ProfileService.getStudentProfileById(userId)
      const payload = (response?.data ?? {}) as {
        user?: PublicProfileUser
        profile?: Record<string, unknown>
      }

      this.profileUser = payload.user ?? {}
      this.form = hydrateStudentProfileForm(payload.profile ?? {})
      await this.loadCvFiles(userId)
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      this.errorMessage = err?.response?.data?.message ?? 'Failed to load student profile.'
    } finally {
      this.loading = false
    }
  },
  methods: {
    async loadCvFiles(userId: number) {
      this.cvLoading = true
      try {
        const response = await ProfileService.getStudentCvFilesByStudentId(userId, {
          projectId: this.projectId ?? undefined,
        })
        this.cvFiles = response.data
      } catch {
        this.cvFiles = []
      } finally {
        this.cvLoading = false
      }
    },
    async handleCvDownload(cv: StudentCvFileItem) {
      if (!this.canDownloadCv(cv.scan_status)) return

      this.downloadingCvId = cv.id
      this.errorMessage = ''

      try {
        const blob = await ProfileService.downloadStudentCv(cv.id, {
          projectId: this.projectId ?? undefined,
        })
        const objectUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = objectUrl
        link.download = cv.original_filename || 'cv-file'
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(objectUrl)
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to download CV.'
      } finally {
        this.downloadingCvId = null
      }
    },
    fieldOrFallback(value: unknown): string {
      const normalized = String(value ?? '').trim()
      return normalized.length ? normalized : 'Not provided'
    },
    formatNumeric(value: number | '' | undefined): string {
      if (value === '' || value === undefined || value === null) return ''
      return String(value)
    },
    formatDate(value: string | null): string {
      if (!value) return 'Unknown'
      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) return 'Unknown'

      return parsed.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    },
    canDownloadCv(scanStatus: string): boolean {
      return scanStatus === 'clean' || scanStatus === 'skipped'
    },
    humanFileSize(sizeBytes: number): string {
      if (!Number.isFinite(sizeBytes) || sizeBytes <= 0) return '0 B'
      const units = ['B', 'KB', 'MB', 'GB']
      let value = sizeBytes
      let unitIndex = 0

      while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024
        unitIndex += 1
      }

      const precision = unitIndex === 0 ? 0 : 1
      return `${value.toFixed(precision)} ${units[unitIndex]}`
    },
  },
})
</script>

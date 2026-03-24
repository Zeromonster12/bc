<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <StudentProfileHeader
        :avatar-preview="avatarPreview"
        :initials="initials"
        :completion-rate="completionRate"
      />

      <BaseAlert
        v-if="successMessage"
        type="success"
        :message="successMessage"
        dismissible
        @dismiss="successMessage = ''"
      />
      <BaseAlert
        v-if="errorMessage"
        type="error"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 8" :key="n" class="h-16 bg-gray-100 rounded-xl animate-pulse" />
      </div>

      <form v-else @submit.prevent="handleSubmit" novalidate class="space-y-6">
        <div class="flex justify-end gap-2">
          <BaseButton
            v-if="!isEditing"
            type="button"
            variant="secondary"
            size="lg"
            @click="startEditing"
          >
            Edit profile
          </BaseButton>
          <template v-else>
            <BaseButton type="button" variant="ghost" size="lg" @click="cancelEditing"
              >Cancel</BaseButton
            >
            <BaseButton type="submit" variant="primary" size="lg" :loading="saving"
              >Save profile</BaseButton
            >
          </template>
        </div>

        <fieldset :disabled="!isEditing" :class="!isEditing ? 'opacity-90' : ''" class="space-y-6">
          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">Identity and Contact</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Profile photo</label>
              <input
                type="file"
                accept="image/*"
                @change="handleAvatar"
                class="text-sm text-gray-500"
              />
              <p v-if="errors.avatar" class="mt-1 text-xs text-red-600">{{ errors.avatar }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <BaseInput
                v-model="form.headline"
                label="Professional headline"
                :error="errors.headline"
                placeholder="Junior backend developer and data enthusiast"
              />
              <BaseInput
                v-model="form.phone"
                label="Phone number"
                :error="errors.phone"
                placeholder="+421 900 123 456"
              />
              <BaseInput
                v-model="form.alternate_email"
                label="Alternate email"
                type="email"
                :error="errors.alternate_email"
                placeholder="me@schoolmail.com"
              />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of birth</label>
                <input
                  v-model="form.date_of_birth"
                  type="date"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select
                  v-model="form.gender"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  <option value="">Select</option>
                  <option value="female">Female</option>
                  <option value="male">Male</option>
                  <option value="non-binary">Non-binary</option>
                  <option value="prefer-not-to-say">Prefer not to say</option>
                </select>
              </div>
            </div>
          </section>

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">Location</h2>
            <div class="grid gap-4 sm:grid-cols-2">
              <BaseInput v-model="form.country" label="Country" :error="errors.country" />
              <BaseInput v-model="form.city" label="City" :error="errors.city" />
              <BaseInput
                v-model="form.address_line"
                label="Street and number"
                :error="errors.address_line"
              />
              <BaseInput
                v-model="form.postal_code"
                label="Postal code"
                :error="errors.postal_code"
              />
            </div>
          </section>

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">Education</h2>
            <div class="grid gap-4 sm:grid-cols-2">
              <BaseInput v-model="form.university" label="University" :error="errors.university" />
              <BaseInput
                v-model="form.faculty"
                label="Faculty or Department"
                :error="errors.faculty"
              />
              <BaseInput
                v-model="form.degree"
                label="Degree"
                :error="errors.degree"
                placeholder="Bc., Ing., MSc"
              />
              <BaseInput
                v-model="form.field_of_study"
                label="Field of study"
                :error="errors.field_of_study"
              />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year of study</label>
                <input
                  v-model.number="form.year_of_study"
                  type="number"
                  min="1"
                  max="8"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
                <p v-if="errors.year_of_study" class="mt-1 text-xs text-red-600">
                  {{ errors.year_of_study }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Expected graduation year</label
                >
                <input
                  v-model.number="form.graduation_year"
                  type="number"
                  min="2000"
                  max="2100"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GPA</label>
                <input
                  v-model="form.gpa"
                  type="text"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="e.g. 1.7"
                />
              </div>
            </div>
          </section>

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">About You</h2>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Short bio</label>
              <textarea
                v-model="form.bio"
                rows="3"
                class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Summarize your profile in 2-3 sentences"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Detailed introduction</label
              >
              <textarea
                v-model="form.about_me"
                rows="5"
                class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Describe your motivation, strengths, and what kind of work you enjoy"
              />
            </div>
          </section>

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">Career Preferences</h2>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                <select
                  v-model="form.availability"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  <option value="">Select</option>
                  <option value="immediately">Immediately</option>
                  <option value="within-1-month">Within 1 month</option>
                  <option value="within-3-months">Within 3 months</option>
                  <option value="custom">Custom</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Preferred work type</label
                >
                <select
                  v-model="form.preferred_work_type"
                  class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  <option value="">Select</option>
                  <option value="internship">Internship</option>
                  <option value="part-time">Part-time</option>
                  <option value="full-time">Full-time</option>
                  <option value="freelance">Freelance</option>
                </select>
              </div>

              <BaseInput
                v-model="form.expected_salary_min"
                label="Expected salary from"
                type="number"
                :error="errors.expected_salary_min"
              />
              <BaseInput
                v-model="form.expected_salary_max"
                label="Expected salary to"
                type="number"
                :error="errors.expected_salary_max"
              />
            </div>

            <ProfileTagInput
              label="Preferred locations"
              tone="emerald"
              :tags="form.preferred_locations"
              :input-value="locationInput"
              placeholder="e.g. Bratislava, Kosice, Remote"
              @update:inputValue="locationInput = $event"
              @add="addTag('preferred_locations', locationInput, 'locationInput')"
              @remove="removeTag('preferred_locations', $event)"
            />
          </section>

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">Skills, Interests, and Links</h2>

            <ProfileTagInput
              label="Skills"
              tone="indigo"
              :tags="form.skills"
              :input-value="skillInput"
              placeholder="e.g. Laravel, Vue, SQL"
              @update:inputValue="skillInput = $event"
              @add="addTag('skills', skillInput, 'skillInput')"
              @remove="removeTag('skills', $event)"
            />

            <ProfileTagInput
              label="Interests"
              tone="amber"
              :tags="form.interests"
              :input-value="interestInput"
              placeholder="e.g. AI, cybersecurity, fintech"
              @update:inputValue="interestInput = $event"
              @add="addTag('interests', interestInput, 'interestInput')"
              @remove="removeTag('interests', $event)"
            />

            <div class="grid gap-4 sm:grid-cols-2">
              <BaseInput
                v-model="form.portfolio_url"
                label="Portfolio URL"
                type="url"
                :error="errors.portfolio_url"
              />
              <BaseInput v-model="form.cv_url" label="CV URL" type="url" :error="errors.cv_url" />
              <BaseInput
                v-model="form.website_url"
                label="Personal website"
                type="url"
                :error="errors.website_url"
              />
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm font-semibold text-slate-900">GitHub account connection</p>
                  <p class="mt-1 text-xs text-slate-600">
                    <span v-if="githubConnectionLoading">Loading connection status...</span>
                    <template v-else-if="githubConnected">
                      Connected
                      <a
                        v-if="githubConnection?.profile_url"
                        :href="githubConnection.profile_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-teal-700 hover:text-teal-800"
                      >
                        {{
                          githubConnection.username
                            ? `as @${githubConnection.username}`
                            : 'GitHub profile'
                        }}
                      </a>
                      <span v-else>
                        {{ githubConnection?.username ? `as @${githubConnection.username}` : '' }}
                      </span>
                    </template>
                    <span v-else
                      >Not connected yet. Connect your account for faster profile trust
                      checks.</span
                    >
                  </p>
                </div>

                <div class="flex items-center gap-2">
                  <BaseButton
                    v-if="!githubConnected"
                    type="button"
                    variant="secondary"
                    :loading="githubActionLoading"
                    :disabled="githubConnectionLoading"
                    @click="handleGitHubConnect"
                  >
                    Connect GitHub
                  </BaseButton>
                  <BaseButton
                    v-else
                    type="button"
                    variant="ghost"
                    :loading="githubActionLoading"
                    @click="handleGitHubDisconnect"
                  >
                    Disconnect
                  </BaseButton>
                </div>
              </div>

              <div
                v-if="githubConnected && githubConnection"
                class="mt-4 rounded-lg border border-slate-200 bg-white p-3"
              >
                <div class="flex items-center gap-3">
                  <img
                    v-if="githubConnection.avatar_url"
                    :src="githubConnection.avatar_url"
                    alt="GitHub avatar"
                    class="h-10 w-10 rounded-full border border-slate-200 object-cover"
                  />
                  <div>
                    <p class="text-sm font-medium text-slate-900">
                      {{
                        githubConnection.username
                          ? `@${githubConnection.username}`
                          : 'Connected GitHub account'
                      }}
                    </p>
                    <p class="text-xs text-slate-500">
                      Connected on {{ formatDateTime(githubConnection.connected_at) }}
                    </p>
                  </div>
                </div>
                <div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                  <div>
                    <span class="font-medium text-slate-700">Profile URL:</span>
                    <a
                      v-if="githubConnection.profile_url"
                      :href="githubConnection.profile_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="ml-1 text-teal-700 hover:text-teal-800"
                    >
                      {{ githubConnection.profile_url }}
                    </a>
                    <span v-else class="ml-1">Unavailable</span>
                  </div>
                  <div>
                    <span class="font-medium text-slate-700">Username:</span>
                    <span class="ml-1">{{ githubConnection.username || 'Unavailable' }}</span>
                  </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Recent repositories
                    </p>
                    <p v-if="githubInsightsLoading" class="mt-2 text-xs text-slate-500">
                      Loading repositories...
                    </p>
                    <ul v-else-if="githubRepositories.length > 0" class="mt-2 space-y-2">
                      <li
                        v-for="repo in githubRepositories"
                        :key="repo.url"
                        class="rounded-md border border-slate-200 px-2 py-1.5"
                      >
                        <a
                          :href="repo.url"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-xs font-medium text-teal-700 hover:text-teal-800"
                        >
                          {{ repo.name }}
                        </a>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                          {{ repo.language || 'Unknown language' }} · ★ {{ repo.stars }}
                        </p>
                      </li>
                    </ul>
                    <p v-else class="mt-2 text-xs text-slate-500">No public repositories found.</p>
                  </div>

                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Recent commits
                    </p>
                    <p v-if="githubInsightsLoading" class="mt-2 text-xs text-slate-500">
                      Loading commits...
                    </p>
                    <ul v-else-if="githubRecentCommits.length > 0" class="mt-2 space-y-2">
                      <li
                        v-for="commit in githubRecentCommits"
                        :key="`${commit.sha}-${commit.pushed_at}`"
                        class="rounded-md border border-slate-200 px-2 py-1.5"
                      >
                        <p class="text-xs font-medium text-slate-700">{{ commit.message }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                          {{ commit.repo }} · {{ shortSha(commit.sha) }} ·
                          {{ formatDateTime(commit.pushed_at) }}
                        </p>
                      </li>
                    </ul>
                    <p v-else class="mt-2 text-xs text-slate-500">
                      No recent public commit activity found.
                    </p>
                  </div>
                </div>

                <div class="mt-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Contribution heatmap
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500">
                    Public contributions over the last year.
                  </p>
                  <div class="mt-2 overflow-x-auto rounded-md border border-slate-200 bg-white p-2">
                    <img
                      v-if="githubConnection.username"
                      :src="githubHeatmapUrl(githubConnection.username)"
                      :alt="`GitHub contribution heatmap for ${githubConnection.username}`"
                      class="min-w-180"
                      loading="lazy"
                    />
                    <p v-else class="text-xs text-slate-500">
                      Heatmap unavailable because GitHub username is missing.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <StudentLanguagesEditor
            :languages="form.languages"
            @add="addLanguage"
            @remove="removeLanguage"
            @update-name="updateLanguageName"
            @update-level="updateLanguageLevel"
          />

          <StudentCertificationsEditor
            :certifications="form.certifications"
            @add="addCertification"
            @remove="removeCertification"
            @update-field="updateCertificationField"
          />

          <StudentProjectsEditor
            :projects="form.projects"
            @add="addProject"
            @remove="removeProject"
            @update-field="updateProjectField"
          />

          <section class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900">CV Upload (Secure Storage)</h2>
            <p class="text-sm text-slate-600">
              Upload your CV as PDF, DOC, or DOCX. Files are stored in private storage and are not
              publicly accessible.
            </p>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
              <input
                type="file"
                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                @change="handleCvFileSelected"
                class="text-sm text-slate-600"
              />
              <BaseButton
                type="button"
                variant="secondary"
                :loading="cvUploading"
                :disabled="!cvFileToUpload"
                @click="handleCvUpload"
              >
                Upload CV
              </BaseButton>
            </div>

            <p class="text-xs text-slate-500">Max size: 5 MB. Allowed formats: PDF, DOC, DOCX.</p>

            <div v-if="cvUploading" class="space-y-1">
              <p class="text-xs font-medium text-slate-700">
                {{ cvUploadStatusText() }} {{ cvUploadProgress }}%
              </p>
              <div class="h-2 w-full overflow-hidden rounded bg-slate-200">
                <div
                  class="h-full bg-teal-600 transition-all duration-300"
                  :style="{ width: `${cvUploadProgress}%` }"
                ></div>
              </div>
            </div>

            <div v-if="cvLoading" class="space-y-2">
              <div v-for="n in 2" :key="n" class="h-10 rounded-lg bg-gray-100 animate-pulse"></div>
            </div>

            <div v-else-if="cvFiles.length === 0" class="text-sm text-slate-500">
              No CV uploaded yet.
            </div>

            <ul v-else class="space-y-2">
              <li
                v-for="cv in cvFiles"
                :key="cv.id"
                class="flex flex-col gap-2 rounded-lg border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <div>
                  <p class="text-sm font-medium text-slate-800">{{ cv.original_filename }}</p>
                  <p class="text-xs text-slate-500">
                    {{ humanFileSize(cv.size_bytes) }} · Uploaded
                    {{ formatDateTime(cv.uploaded_at) }}
                  </p>
                  <p class="text-xs" :class="cvScanStatusClass(cv.scan_status)">
                    Security scan: {{ cvScanStatusLabel(cv.scan_status) }}
                    <span v-if="cv.scanned_at"> · {{ formatDateTime(cv.scanned_at) }}</span>
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="text-xs font-medium"
                    :class="
                      canDownloadCv(cv.scan_status)
                        ? 'text-teal-700 hover:text-teal-800'
                        : 'pointer-events-none text-slate-400'
                    "
                    :disabled="!canDownloadCv(cv.scan_status) || downloadingCvId === cv.id"
                    @click="handleCvDownload(cv)"
                  >
                    {{ downloadingCvId === cv.id ? 'Downloading...' : 'Download' }}
                  </button>
                  <BaseButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    :loading="deletingCvId === cv.id"
                    @click="handleCvDelete(cv.id)"
                  >
                    Delete
                  </BaseButton>
                </div>
              </li>
            </ul>
          </section>

        </fieldset>
      </form>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import ProfileService, {
  type GitHubCommitInsight,
  type GitHubConnectionData,
  type GitHubRepositoryInsight,
  type StudentCvFileItem,
} from '@/services/profile/ProfileService'
import {
  type LanguageLevel,
  type StudentCertification,
  type StudentProfileForm,
  type StudentProject,
  addTagToField,
  calculateStudentProfileCompletion,
  createDefaultStudentProfileForm,
  hydrateStudentProfileForm,
  sanitizeStudentProfileForm,
  toStudentProfileFormData,
  validateStudentProfileForm,
} from '@/services/profile/StudentProfileFormService'
import { mapValidationErrors } from '@/services/shared/FormUtilsService'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import StudentProfileHeader from '@/components/profile/StudentProfileHeader.vue'
import ProfileTagInput from '@/components/profile/ProfileTagInput.vue'
import StudentLanguagesEditor from '@/components/profile/StudentLanguagesEditor.vue'
import StudentCertificationsEditor from '@/components/profile/StudentCertificationsEditor.vue'
import StudentProjectsEditor from '@/components/profile/StudentProjectsEditor.vue'
import { resolveAssetUrl } from '@/services/core/url'

type TagField = 'skills' | 'interests' | 'preferred_locations'
type InputField = 'skillInput' | 'interestInput' | 'locationInput'

export default defineComponent({
  name: 'StudentProfileView',
  components: {
    AppLayout,
    BaseInput,
    BaseButton,
    BaseAlert,
    StudentProfileHeader,
    ProfileTagInput,
    StudentLanguagesEditor,
    StudentCertificationsEditor,
    StudentProjectsEditor,
  },
  setup() {
    return { auth: useAuthStore() }
  },
  data() {
    return {
      form: createDefaultStudentProfileForm(),
      avatarFile: null as File | null,
      avatarPreview: '',
      skillInput: '',
      interestInput: '',
      locationInput: '',
      errors: {} as Record<string, string>,
      successMessage: '',
      errorMessage: '',
      loading: true,
      saving: false,
      isEditing: false,
      initialFormSnapshot: createDefaultStudentProfileForm() as StudentProfileForm,
      initialAvatarPreview: '',
      githubConnected: false,
      githubConnection: null as GitHubConnectionData | null,
      githubConnectionLoading: true,
      githubActionLoading: false,
      githubInsightsLoading: false,
      githubRepositories: [] as GitHubRepositoryInsight[],
      githubRecentCommits: [] as GitHubCommitInsight[],
      cvLoading: false,
      cvUploading: false,
      cvUploadProgress: 0,
      cvUploadPhase: 'idle' as 'idle' | 'uploading' | 'scanning',
      cvFileToUpload: null as File | null,
      cvFiles: [] as StudentCvFileItem[],
      downloadingCvId: null as number | null,
      deletingCvId: null as number | null,
    }
  },
  computed: {
    initials(): string {
      return (this.auth.user?.name ?? 'U')
        .split(' ')
        .map((word: string) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
    completionRate(): number {
      return calculateStudentProfileCompletion(this.form)
    },
  },
  async mounted() {
    if (this.$route.query.github === 'connected') {
      this.successMessage = 'GitHub account connected successfully.'
    }

    try {
      const data = await ProfileService.getStudentProfile()
      this.form = hydrateStudentProfileForm(data)
      if (typeof data.avatar_url === 'string' && data.avatar_url) {
        this.avatarPreview = resolveAssetUrl(data.avatar_url)
      }

      this.initialFormSnapshot = this.cloneForm(this.form)
      this.initialAvatarPreview = this.avatarPreview
    } catch {
      // Keep default values for a new profile.
      this.initialFormSnapshot = this.cloneForm(this.form)
      this.initialAvatarPreview = this.avatarPreview
    } finally {
      this.loading = false
    }

    await this.loadGitHubConnection()
    await this.loadCvFiles()
  },
  methods: {
    cloneForm(form: StudentProfileForm): StudentProfileForm {
      return JSON.parse(JSON.stringify(form)) as StudentProfileForm
    },
    startEditing() {
      this.isEditing = true
      this.errorMessage = ''
      this.successMessage = ''
    },
    cancelEditing() {
      this.form = this.cloneForm(this.initialFormSnapshot)
      this.avatarPreview = this.initialAvatarPreview
      this.avatarFile = null
      this.errors = {}
      this.errorMessage = ''
      this.successMessage = ''
      this.isEditing = false
    },
    async loadGitHubConnection() {
      this.githubConnectionLoading = true
      try {
        const response = await ProfileService.getGitHubConnectionStatus()
        this.githubConnected = response.connected
        this.githubConnection = response.data

        if (response.connected && response.data?.profile_url && !this.form.github_url) {
          this.form.github_url = response.data.profile_url
        }

        if (response.connected) {
          await this.loadGitHubInsights()
        }
      } catch {
        this.githubConnected = false
        this.githubConnection = null
        this.githubRepositories = []
        this.githubRecentCommits = []
      } finally {
        this.githubConnectionLoading = false
      }
    },
    async loadGitHubInsights() {
      this.githubInsightsLoading = true
      try {
        const response = await ProfileService.getGitHubInsights()
        this.githubRepositories = response.data.repositories
        this.githubRecentCommits = response.data.recent_commits
      } catch {
        this.githubRepositories = []
        this.githubRecentCommits = []
      } finally {
        this.githubInsightsLoading = false
      }
    },
    async handleGitHubConnect() {
      this.errorMessage = ''
      this.githubActionLoading = true

      try {
        const response = await ProfileService.getGitHubConnectionRedirectUrl()
        window.location.href = response.url
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage =
          typedError?.response?.data?.message ?? 'GitHub connection is unavailable right now.'
      } finally {
        this.githubActionLoading = false
      }
    },
    async handleGitHubDisconnect() {
      this.errorMessage = ''
      this.successMessage = ''
      this.githubActionLoading = true

      try {
        await ProfileService.disconnectGitHubConnection()
        this.githubConnected = false
        this.githubConnection = null
        this.githubRepositories = []
        this.githubRecentCommits = []
        this.successMessage = 'GitHub account disconnected.'
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage =
          typedError?.response?.data?.message ?? 'Failed to disconnect GitHub account.'
      } finally {
        this.githubActionLoading = false
      }
    },
    formatDateTime(value: string | null): string {
      if (!value) return 'Unknown'
      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) return 'Unknown'
      return parsed.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    shortSha(sha: string): string {
      if (!sha) return 'unknown'
      return sha.slice(0, 7)
    },
    githubHeatmapUrl(username: string): string {
      const normalized = encodeURIComponent(username.trim())
      return `https://ghchart.rshah.org/16a34a/${normalized}`
    },
    async loadCvFiles() {
      this.cvLoading = true
      try {
        const response = await ProfileService.getStudentCvFiles()
        this.cvFiles = response.data
      } catch {
        this.cvFiles = []
      } finally {
        this.cvLoading = false
      }
    },
    handleCvFileSelected(event: Event) {
      const file = (event.target as HTMLInputElement).files?.[0] ?? null
      this.cvFileToUpload = file
    },
    async handleCvUpload() {
      if (!this.cvFileToUpload) return

      this.cvUploading = true
      this.cvUploadProgress = 1
      this.cvUploadPhase = 'uploading'
      this.errorMessage = ''
      this.successMessage = ''

      try {
        await ProfileService.uploadStudentCv(this.cvFileToUpload, (progress) => {
          if (progress >= 100) {
            this.cvUploadPhase = 'scanning'
            this.cvUploadProgress = 99
            return
          }

          this.cvUploadProgress = progress
          this.cvUploadPhase = 'uploading'
        })

        this.cvUploadProgress = 100
        this.successMessage = 'CV uploaded successfully.'
        this.cvFileToUpload = null
        await this.loadCvFiles()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to upload CV.'
      } finally {
        this.cvUploading = false
        this.cvUploadProgress = 0
        this.cvUploadPhase = 'idle'
      }
    },
    async handleCvDelete(cvId: number) {
      this.deletingCvId = cvId
      this.errorMessage = ''

      try {
        await ProfileService.deleteStudentCv(cvId)
        this.successMessage = 'CV deleted successfully.'
        await this.loadCvFiles()
      } catch (error: unknown) {
        const typedError = error as { response?: { data?: { message?: string } } }
        this.errorMessage = typedError?.response?.data?.message ?? 'Failed to delete CV.'
      } finally {
        this.deletingCvId = null
      }
    },
    async handleCvDownload(cv: StudentCvFileItem) {
      if (!this.canDownloadCv(cv.scan_status)) return

      this.downloadingCvId = cv.id
      this.errorMessage = ''

      try {
        const blob = await ProfileService.downloadStudentCv(cv.id)
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
    cvUploadStatusText(): string {
      if (this.cvUploadPhase === 'scanning') return 'Testing for viruses...'
      return 'Uploading CV...'
    },
    canDownloadCv(scanStatus: string): boolean {
      return scanStatus === 'clean' || scanStatus === 'skipped'
    },
    cvScanStatusLabel(scanStatus: string): string {
      if (scanStatus === 'clean') return 'Clean'
      if (scanStatus === 'skipped') return 'Skipped (scanner disabled)'
      if (scanStatus === 'infected') return 'Blocked - malware detected'
      if (scanStatus === 'scan_error') return 'Unavailable - scan error'
      return 'Pending'
    },
    cvScanStatusClass(scanStatus: string): string {
      if (scanStatus === 'clean') return 'text-emerald-700'
      if (scanStatus === 'skipped') return 'text-amber-700'
      if (scanStatus === 'infected') return 'text-red-700'
      if (scanStatus === 'scan_error') return 'text-red-700'
      return 'text-slate-500'
    },
    handleAvatar(event: Event) {
      const file = (event.target as HTMLInputElement).files?.[0]
      if (!file) return
      this.avatarFile = file
      this.avatarPreview = URL.createObjectURL(file)
    },
    addTag(field: TagField, rawValue: string, inputField: InputField) {
      this.form = addTagToField(this.form, field, rawValue)
      this[inputField] = ''
    },
    removeTag(field: TagField, index: number) {
      this.form[field].splice(index, 1)
    },
    addLanguage() {
      this.form.languages.push({ name: '', level: '' })
    },
    removeLanguage(index: number) {
      this.form.languages.splice(index, 1)
    },
    updateLanguageName(payload: { index: number; value: string }) {
      const language = this.form.languages[payload.index]
      if (!language) return
      language.name = payload.value
    },
    updateLanguageLevel(payload: { index: number; value: string }) {
      const language = this.form.languages[payload.index]
      if (!language) return
      language.level = payload.value as LanguageLevel
    },
    addCertification() {
      this.form.certifications.push({ name: '', issuer: '', year: '' })
    },
    removeCertification(index: number) {
      this.form.certifications.splice(index, 1)
    },
    updateCertificationField(payload: {
      index: number
      field: keyof StudentCertification
      value: string
    }) {
      const certification = this.form.certifications[payload.index]
      if (!certification) return
      certification[payload.field] = payload.value
    },
    addProject() {
      this.form.projects.push({ title: '', tech: '', link: '', description: '' })
    },
    removeProject(index: number) {
      this.form.projects.splice(index, 1)
    },
    updateProjectField(payload: { index: number; field: keyof StudentProject; value: string }) {
      const project = this.form.projects[payload.index]
      if (!project) return
      project[payload.field] = payload.value
    },
    async handleSubmit() {
      if (!this.isEditing) return

      this.successMessage = ''
      this.errorMessage = ''

      this.form = sanitizeStudentProfileForm(this.form)
      this.errors = validateStudentProfileForm(this.form)
      if (Object.keys(this.errors).length > 0) {
        this.errorMessage = 'Please fix the highlighted fields and try again.'
        return
      }

      this.saving = true
      this.errors = {}

      try {
        const payload = toStudentProfileFormData(this.form, this.avatarFile)
        await ProfileService.updateStudentProfile(payload)
        this.successMessage = 'Profile updated successfully.'
        this.isEditing = false
        this.avatarFile = null
        this.initialFormSnapshot = this.cloneForm(this.form)
        this.initialAvatarPreview = this.avatarPreview
      } catch (error: unknown) {
        const typedError = error as {
          response?: {
            status?: number
            data?: {
              errors?: Record<string, string[]>
              message?: string
            }
          }
        }

        if (typedError?.response?.status === 422) {
          const backendErrors = typedError.response.data?.errors ?? {}
          this.errors = mapValidationErrors(backendErrors)
          this.errorMessage = 'Validation failed. Please review your input.'
        } else {
          this.errorMessage = typedError?.response?.data?.message ?? 'Failed to save profile.'
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>

<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <StudentProfileHeader
        :avatar-preview="avatarPreview"
        :initials="initials"
        :student-name="studentDisplayName"
        :student-bio="studentDisplayBio"
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

      <div v-if="!loading" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="surface-card p-6 sm:p-7">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Recent Activity</h2>
            <span class="rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
              Profile
            </span>
          </div>

          <div class="mt-4 space-y-2.5">
            <div
              v-for="activity in recentProfileActivities"
              :key="activity.id"
              class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60"
            >
              <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ activity.title }}</p>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ activity.meta }}</p>
            </div>
          </div>
        </section>

        <section class="surface-card p-6 sm:p-7">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Next Steps</h2>
            <span class="rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
              {{ pendingProfileStepsCount }} pending
            </span>
          </div>

          <ul class="mt-4 space-y-2.5">
            <li
              v-for="item in profileNextSteps"
              :key="item.key"
              class="flex items-start gap-3 rounded-xl border px-3 py-2.5"
              :class="item.done
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200'"
            >
              <span
                class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full text-[11px] font-black"
                :class="item.done ? 'bg-emerald-600 text-white dark:bg-emerald-500' : 'bg-slate-300 text-slate-700 dark:bg-slate-600 dark:text-slate-100'"
              >
                {{ item.done ? '✓' : '•' }}
              </span>
              <div>
                <p class="text-sm font-semibold">{{ item.label }}</p>
                <p class="text-xs opacity-80">{{ item.note }}</p>
              </div>
            </li>
          </ul>
        </section>
      </div>

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 8" :key="n" class="h-16 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
      </div>

      <form v-else @submit.prevent="handleSubmit" novalidate class="space-y-6">
        <div class="p-3 sm:p-4">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tab in profileTabs"
              :key="tab.id"
              type="button"
              class="rounded-full px-5 py-2 text-sm font-medium transition"
              :class="
                activeTab === tab.id
                  ? 'bg-[#4e3aba] text-white shadow-sm'
                  : 'bg-[#4e3aba]/10 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
              "
              @click="activeTab = tab.id"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <fieldset class="space-y-6">
          <section v-show="activeTab === 'personal'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Identity and Contact</h2>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Profile photo</label>
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                <label
                  for="avatar-upload"
                  class="inline-flex cursor-pointer items-center justify-center rounded-full bg-[#4e3aba] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#3f2ea1]"
                >
                  Choose photo
                </label>
                <input
                  id="avatar-upload"
                  type="file"
                  accept="image/*"
                  @change="handleAvatar"
                  class="sr-only"
                />
                <span class="text-xs text-slate-500 dark:text-slate-400">
                  {{ avatarFileName || 'No photo selected' }}
                </span>
              </div>
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
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Date of birth</label>
                <input
                  v-model="form.date_of_birth"
                  type="date"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Gender</label>
                <select
                  v-model="form.gender"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
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

          <section v-show="activeTab === 'personal'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Location</h2>
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

          <section v-show="activeTab === 'education'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Education</h2>
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
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Year of study</label>
                <input
                  v-model.number="form.year_of_study"
                  type="number"
                  min="1"
                  max="8"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
                <p v-if="errors.year_of_study" class="mt-1 text-xs text-red-600">
                  {{ errors.year_of_study }}
                </p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300"
                  >Expected graduation year</label
                >
                <input
                  v-model.number="form.graduation_year"
                  type="number"
                  min="2000"
                  max="2100"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">GPA</label>
                <input
                  v-model="form.gpa"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  placeholder="e.g. 1.7"
                />
              </div>
            </div>
          </section>

          <section v-show="activeTab === 'personal'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">About You</h2>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Short bio</label>
              <textarea
                v-model="form.bio"
                rows="3"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                placeholder="Summarize your profile in 2-3 sentences"
              />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300"
                >Detailed introduction</label
              >
              <textarea
                v-model="form.about_me"
                rows="5"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                placeholder="Describe your motivation, strengths, and what kind of work you enjoy"
              />
            </div>
          </section>

          <section v-show="activeTab === 'skills'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Skills, Interests, and Links</h2>

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
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/60">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">GitHub account connection</p>
                  <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                    <span v-if="githubConnectionLoading">Loading connection status...</span>
                    <template v-else-if="githubConnected">
                      Connected
                      <a
                        v-if="githubConnection?.profile_url"
                        :href="githubConnection.profile_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
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
                class="mt-4 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
              >
                <div class="flex items-center gap-3">
                  <img
                    v-if="githubConnection.avatar_url"
                    :src="githubConnection.avatar_url"
                    alt="GitHub avatar"
                    class="h-10 w-10 rounded-full border border-slate-200 object-cover dark:border-slate-600"
                  />
                  <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                      {{
                        githubConnection.username
                          ? `@${githubConnection.username}`
                          : 'Connected GitHub account'
                      }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Connected on {{ formatDateTime(githubConnection.connected_at) }}
                    </p>
                  </div>
                </div>
                <div class="mt-3 grid gap-2 text-xs text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                  <div>
                    <span class="font-medium text-slate-700 dark:text-slate-200">Profile URL:</span>
                    <a
                      v-if="githubConnection.profile_url"
                      :href="githubConnection.profile_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="ml-1 text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
                    >
                      {{ githubConnection.profile_url }}
                    </a>
                    <span v-else class="ml-1">Unavailable</span>
                  </div>
                  <div>
                    <span class="font-medium text-slate-700 dark:text-slate-200">Username:</span>
                    <span class="ml-1">{{ githubConnection.username || 'Unavailable' }}</span>
                  </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      Recent repositories
                    </p>
                    <p v-if="githubInsightsLoading" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                      Loading repositories...
                    </p>
                    <ul v-else-if="githubRepositories.length > 0" class="mt-2 space-y-2">
                      <li
                        v-for="repo in githubRepositories"
                        :key="repo.url"
                        class="rounded-md border border-slate-200 px-2 py-1.5 dark:border-slate-700"
                      >
                        <a
                          :href="repo.url"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
                        >
                          {{ repo.name }}
                        </a>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                          {{ repo.language || 'Unknown language' }} · ★ {{ repo.stars }}
                        </p>
                      </li>
                    </ul>
                    <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">No public repositories found.</p>
                  </div>

                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      Recent commits
                    </p>
                    <p v-if="githubInsightsLoading" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                      Loading commits...
                    </p>
                    <ul v-else-if="githubRecentCommits.length > 0" class="mt-2 space-y-2">
                      <li
                        v-for="commit in githubRecentCommits"
                        :key="`${commit.sha}-${commit.pushed_at}`"
                        class="rounded-md border border-slate-200 px-2 py-1.5 dark:border-slate-700"
                      >
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ commit.message }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                          {{ commit.repo }} · {{ shortSha(commit.sha) }} ·
                          {{ formatDateTime(commit.pushed_at) }}
                        </p>
                      </li>
                    </ul>
                    <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                      No recent public commit activity found.
                    </p>
                  </div>
                </div>

                <div class="mt-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Contribution heatmap
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Public contributions over the last year.
                  </p>
                  <div class="mt-2 overflow-x-auto rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
                    <img
                      v-if="githubConnection.username"
                      :src="githubHeatmapUrl(githubConnection.username)"
                      :alt="`GitHub contribution heatmap for ${githubConnection.username}`"
                      class="min-w-180"
                      loading="lazy"
                    />
                    <p v-else class="text-xs text-slate-500 dark:text-slate-400">
                      Heatmap unavailable because GitHub username is missing.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <StudentLanguagesEditor
            v-show="activeTab === 'portfolio'"
            :languages="form.languages"
            @add="addLanguage"
            @remove="removeLanguage"
            @update-name="updateLanguageName"
            @update-level="updateLanguageLevel"
          />

          <StudentCertificationsEditor
            v-show="activeTab === 'portfolio'"
            :certifications="form.certifications"
            @add="addCertification"
            @remove="removeCertification"
            @update-field="updateCertificationField"
          />

          <StudentProjectsEditor
            v-show="activeTab === 'portfolio'"
            :projects="form.projects"
            @add="addProject"
            @remove="removeProject"
            @update-field="updateProjectField"
          />

          <section v-show="activeTab === 'documents'" class="surface-card p-6 sm:p-7 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">CV Upload (Secure Storage)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">
              Upload your CV as PDF, DOC, or DOCX. Files are stored in private storage and are not
              publicly accessible.
            </p>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
              <label
                for="cv-upload"
                class="inline-flex cursor-pointer items-center justify-center rounded-full bg-[#4e3aba] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#3f2ea1]"
              >
                Choose CV file
              </label>
              <input
                id="cv-upload"
                type="file"
                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                @change="handleCvFileSelected"
                class="sr-only"
              />
              <span class="text-xs text-slate-500 dark:text-slate-400">{{ cvFileName || 'No file selected' }}</span>
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

            <p class="text-xs text-slate-500 dark:text-slate-400">Max size: 5 MB. Allowed formats: PDF, DOC, DOCX.</p>

            <div v-if="cvUploading" class="space-y-1">
              <p class="text-xs font-medium text-slate-700 dark:text-slate-200">
                {{ cvUploadStatusText() }} {{ cvUploadProgress }}%
              </p>
              <div class="h-2 w-full overflow-hidden rounded bg-slate-200 dark:bg-slate-700">
                <div
                  class="h-full bg-teal-600 transition-all duration-300"
                  :style="{ width: `${cvUploadProgress}%` }"
                ></div>
              </div>
            </div>

            <div v-if="cvLoading" class="space-y-2">
              <div
                v-for="n in 2"
                :key="n"
                class="h-10 rounded-lg bg-gray-100 animate-pulse dark:bg-slate-800"
              ></div>
            </div>

            <div v-else-if="cvFiles.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
              No CV uploaded yet.
            </div>

            <ul v-else class="space-y-2">
              <li
                v-for="cv in cvFiles"
                :key="cv.id"
                class="flex flex-col gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"
              >
                <div>
                  <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ cv.original_filename }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
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
                        ? 'text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200'
                        : 'pointer-events-none text-slate-400 dark:text-slate-500'
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

          <div class="surface-card p-4 sm:p-5">
            <div class="flex justify-end">
              <BaseButton
                type="submit"
                variant="primary"
                size="lg"
                :loading="saving"
                class="rounded-full! px-6"
              >
                {{ saveButtonLabel }}
              </BaseButton>
            </div>
          </div>
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

type TagField = 'skills' | 'interests'
type InputField = 'skillInput' | 'interestInput'
type ProfileTab = 'personal' | 'education' | 'skills' | 'portfolio' | 'documents'

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
      avatarFileName: '',
      avatarPreview: '',
      skillInput: '',
      interestInput: '',
      errors: {} as Record<string, string>,
      successMessage: '',
      errorMessage: '',
      loading: true,
      saving: false,
      activeTab: 'personal' as ProfileTab,
      profileTabs: [
        { id: 'personal' as ProfileTab, label: 'Personal Information' },
        { id: 'education' as ProfileTab, label: 'Education' },
        { id: 'skills' as ProfileTab, label: 'Skills & Links' },
        { id: 'portfolio' as ProfileTab, label: 'Portfolio' },
        { id: 'documents' as ProfileTab, label: 'Documents' },
      ],
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
      cvFileName: '',
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
    studentDisplayName(): string {
      const name = this.auth.user?.name?.trim()
      return name && name.length > 0 ? name : 'Student Profile'
    },
    studentDisplayBio(): string {
      const bio = this.form.bio?.trim()
      return bio && bio.length > 0
        ? bio
        : 'Add a short bio so companies can quickly understand your profile.'
    },
    saveButtonLabel(): string {
      if (this.activeTab === 'personal') return 'Save personal information'
      if (this.activeTab === 'education') return 'Save education'
      if (this.activeTab === 'skills') return 'Save skills and links'
      if (this.activeTab === 'portfolio') return 'Save portfolio'
      return 'Save profile'
    },
    recentProfileActivities(): Array<{ id: string; title: string; meta: string }> {
      const latestCv = this.cvFiles[0]

      return [
        {
          id: 'completion',
          title: 'Profile completion updated',
          meta: `${this.completionRate}% complete`,
        },
        this.githubConnected
          ? {
              id: 'github-connected',
              title: `GitHub connected${this.githubConnection?.username ? ` as @${this.githubConnection.username}` : ''}`,
              meta: this.formatRelativeTime(this.githubConnection?.connected_at ?? null),
            }
          : {
              id: 'github-missing',
              title: 'GitHub account not connected yet',
              meta: 'Connect it in Skills & Links for better profile trust.',
            },
        latestCv
          ? {
              id: 'cv-uploaded',
              title: `CV ready: ${latestCv.original_filename}`,
              meta: `Uploaded ${this.formatRelativeTime(latestCv.uploaded_at)}`,
            }
          : {
              id: 'cv-missing',
              title: 'No CV uploaded yet',
              meta: 'Add a CV in Documents so companies can review your profile faster.',
            },
      ]
    },
    profileNextSteps(): Array<{ key: string; label: string; note: string; done: boolean }> {
      const hasHeadline = this.form.headline.trim().length > 0
      const hasSkills = this.form.skills.length >= 3
      const hasCv = this.cvFiles.length > 0
      const hasGitHub = this.githubConnected

      return [
        {
          key: 'headline',
          label: 'Set a professional headline',
          note: hasHeadline ? 'Headline is set.' : 'Add a short headline in Personal Information.',
          done: hasHeadline,
        },
        {
          key: 'skills',
          label: 'Add at least 3 skills',
          note: hasSkills ? `${this.form.skills.length} skills added.` : 'List your strongest technical skills.',
          done: hasSkills,
        },
        {
          key: 'github',
          label: 'Connect GitHub account',
          note: hasGitHub ? 'GitHub connection active.' : 'Connect GitHub in Skills & Links tab.',
          done: hasGitHub,
        },
        {
          key: 'cv',
          label: 'Upload your CV document',
          note: hasCv ? `${this.cvFiles.length} CV file(s) uploaded.` : 'Upload CV in Documents tab.',
          done: hasCv,
        },
      ]
    },
    pendingProfileStepsCount(): number {
      return this.profileNextSteps.filter((step) => !step.done).length
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

    } catch {
      // Keep default values for a new profile.
    } finally {
      this.loading = false
    }

    await this.loadGitHubConnection()
    await this.loadCvFiles()
  },
  methods: {
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
    formatRelativeTime(value: string | null): string {
      if (!value) return 'recently'

      const parsed = new Date(value)
      if (Number.isNaN(parsed.getTime())) return 'recently'

      const diffMs = Date.now() - parsed.getTime()
      if (diffMs < 60 * 1000) return 'just now'

      const diffMinutes = Math.floor(diffMs / (60 * 1000))
      if (diffMinutes < 60) return `${diffMinutes}m ago`

      const diffHours = Math.floor(diffMinutes / 60)
      if (diffHours < 24) return `${diffHours}h ago`

      const diffDays = Math.floor(diffHours / 24)
      if (diffDays < 7) return `${diffDays}d ago`

      const diffWeeks = Math.floor(diffDays / 7)
      return `${diffWeeks}w ago`
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
      this.cvFileName = file?.name ?? ''
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
        this.cvFileName = ''
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
      if (!file) {
        this.avatarFile = null
        this.avatarFileName = ''
        return
      }
      this.avatarFile = file
      this.avatarFileName = file.name
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
        this.avatarFile = null
        this.avatarFileName = ''
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

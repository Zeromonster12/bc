<template>
  <AppLayout>
    <div class="mx-auto w-full max-w-7xl space-y-6 pb-8">
      <section class="rounded-3xl border border-[#ddd7ef] bg-white p-6 dark:border-slate-700 dark:bg-slate-900/90 sm:p-8">
        <div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#6f6989] dark:text-slate-400">Projects / New project</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#1f1a38] dark:text-slate-100 sm:text-4xl">Curate a new opportunity</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-[#6c6786] dark:text-slate-300 sm:text-base">
              Define your internship project in detail so students instantly understand mission, impact and expected outcomes.
            </p>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-8 xl:grid-cols-12">
        <div class="xl:col-span-8">
          <BaseAlert
            v-if="errorMessage"
            type="error"
            :message="errorMessage"
            dismissible
            @dismiss="errorMessage = ''"
          />

          <div :class="errorMessage ? 'mt-4' : ''">
            <ProjectCreateForm
              :form="form"
              :tech-input="techInput"
              :errors="errors"
              :loading="loading"
              :show-status-field="false"
              @submit="handleSubmit"
              @save-draft="handleSaveDraft"
              @cancel="$router.back()"
              @update-field="updateField"
              @update:techInput="techInput = $event"
              @add-tech="addTech"
              @remove-tech="removeTech"
            />
          </div>
        </div>

        <aside class="space-y-6 xl:col-span-4">
          <section class="rounded-3xl border border-[#ded8f0] bg-[#f5f2fd] p-6 dark:border-slate-700 dark:bg-slate-900/80">
            <div class="space-y-3">
              <p class="inline-flex rounded-full bg-[#e5ddff] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#4b35cb] dark:bg-indigo-500/20 dark:text-indigo-200">Curator tip</p>
              <h2 class="text-xl font-bold text-[#1f1a38] dark:text-slate-100">Detailed scope gets better applicants</h2>
              <p class="text-sm text-[#605b79] dark:text-slate-300">
                Projects with clear requirements and measurable goals usually receive higher-quality applications in less time.
              </p>
            </div>
          </section>

          <section class="rounded-3xl border border-[#ddd7ef] bg-white p-6 dark:border-slate-700 dark:bg-slate-900/90">
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6f6989] dark:text-slate-400">Live preview</h3>
            <div class="mt-4 rounded-2xl border border-[#e3dcf4] bg-[#f7f4fc] p-4 dark:border-slate-700 dark:bg-slate-800/60">
              <p class="text-base font-bold text-[#1f1a38] dark:text-slate-100">{{ previewTitle }}</p>
              <p class="mt-1 text-sm text-[#625d7c] dark:text-slate-300">{{ previewLocation }}</p>
              <p class="mt-1 text-xs uppercase tracking-widest text-[#7b7595] dark:text-slate-400">
                {{ previewLocationStrategy }} · {{ form.internship_duration }}
              </p>
              <p class="mt-4 line-clamp-4 text-sm text-[#3f3a56] dark:text-slate-200">{{ previewDescription }}</p>

              <div class="mt-4 flex flex-wrap gap-2">
                <span
                  v-for="tag in previewTechStack"
                  :key="tag"
                  class="rounded-full bg-[#e6deff] px-3 py-1 text-xs font-semibold text-[#4735a3] dark:bg-indigo-500/20 dark:text-indigo-200"
                >
                  {{ tag }}
                </span>
              </div>

              <p class="mt-4 text-xs font-medium text-[#746e8f] dark:text-slate-400">
                Status: <span class="font-semibold text-[#3f3a56] dark:text-slate-200">{{ form.status }}</span>
                · Max students: <span class="font-semibold text-[#3f3a56] dark:text-slate-200">{{ form.max_students }}</span>
              </p>
            </div>
          </section>

          <section class="rounded-3xl border border-[#ddd7ef] bg-white p-6 dark:border-slate-700 dark:bg-slate-900/90">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-sm font-bold text-[#1f1a38] dark:text-slate-100">Saved drafts</h3>
              <FileClock class="h-4 w-4 text-[#7c7699] dark:text-slate-400" />
            </div>

            <div v-if="draftsLoading" class="mt-4 space-y-2">
              <div v-for="n in 3" :key="n" class="h-10 animate-pulse rounded-xl bg-[#f1edf8] dark:bg-slate-800" />
            </div>

            <p v-else-if="draftProjects.length === 0" class="mt-4 text-sm text-[#6c6786] dark:text-slate-400">
              You do not have any saved drafts yet.
            </p>

            <ul v-else class="mt-4 space-y-2">
              <li v-for="draft in draftProjects" :key="draft.id">
                <RouterLink
                  :to="'/projects/' + draft.id + '/edit'"
                  class="group flex items-center justify-between rounded-xl border border-[#dfd8ef] bg-[#faf8ff] px-3 py-2 transition hover:border-[#cfc3ef] hover:bg-[#f4eeff] dark:border-slate-700 dark:bg-slate-800/70 dark:hover:border-indigo-400/40 dark:hover:bg-indigo-500/10"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-[#2f2a47] dark:text-slate-100">{{ draft.title || 'Untitled draft' }}</p>
                    <p class="text-xs text-[#7b7596] dark:text-slate-400">Modified {{ formatRelativeDate(draft.created_at) }}</p>
                  </div>
                  <ChevronRight class="h-4 w-4 text-[#9b94b7] transition group-hover:text-[#4f33d7] dark:group-hover:text-indigo-300" />
                </RouterLink>
              </li>
            </ul>
          </section>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { ChevronRight, FileClock } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { mapValidationErrors } from '@/services/shared/FormUtilsService'
import { addTechTag, createDefaultProjectForm } from '@/services/projects/ProjectFormService'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProjectCreateForm from '@/components/projects/ProjectCreateForm.vue'

export default defineComponent({
  name: 'CreateProjectView',
  components: { AppLayout, BaseAlert, ProjectCreateForm, ChevronRight, FileClock },
  setup() {
    return { auth: useAuthStore(), projectStore: useProjectStore() }
  },
  data() {
    return {
      form: createDefaultProjectForm(),
      techInput: '',
      errors: {} as Record<string, string>,
      errorMessage: '',
      loading: false,
      draftsLoading: false,
      draftProjects: [] as Array<{ id: number; title: string; created_at: string }>,
    }
  },
  computed: {
    previewTitle(): string {
      const value = this.form.title.trim()
      return value || 'Your project title appears here'
    },
    previewLocation(): string {
      const value = this.form.location.trim()
      return value || 'Location not specified yet'
    },
    previewDescription(): string {
      const value = this.form.description.trim()
      return value || 'Describe the mission and responsibilities of your project.'
    },
    previewTechStack(): string[] {
      return this.form.tech_stack.length ? this.form.tech_stack : ['Tech stack']
    },
    previewLocationStrategy(): string {
      if (this.form.location_strategy === 'onsite') return 'On-site'
      if (this.form.location_strategy === 'hybrid') return 'Hybrid'
      return 'Remote'
    },
  },
  async mounted() {
    await this.loadDraftProjects()
  },
  methods: {
    updateField(payload: {
      field:
        | 'title'
        | 'location'
        | 'location_strategy'
        | 'industry'
        | 'internship_duration'
        | 'description'
        | 'requirements'
        | 'status'
        | 'max_students'
      value: string | number
    }) {
      ;(this.form[payload.field] as string | number) = payload.value
    },
    addTech() {
      this.form = addTechTag(this.form, this.techInput)
      this.techInput = ''
    },
    removeTech(index: number) {
      this.form.tech_stack.splice(index, 1)
    },
    async loadDraftProjects() {
      if (!this.auth.user?.id) return
      this.draftsLoading = true
      try {
        await this.projectStore.fetchProjects({
          status: 'draft',
          company_id: this.auth.user.id,
          per_page: 6,
        })
        this.draftProjects = this.projectStore.projects.map((project) => ({
          id: project.id,
          title: project.title,
          created_at: project.created_at,
        }))
      } catch {
        this.draftProjects = []
      } finally {
        this.draftsLoading = false
      }
    },
    formatRelativeDate(value: string): string {
      if (!value) return 'recently'
      const now = Date.now()
      const ts = new Date(value).getTime()
      if (Number.isNaN(ts)) return 'recently'
      const diffMs = Math.max(0, now - ts)
      const dayMs = 24 * 60 * 60 * 1000
      const days = Math.floor(diffMs / dayMs)
      if (days <= 0) return 'today'
      if (days === 1) return '1 day ago'
      if (days < 30) return `${days} days ago`
      const months = Math.floor(days / 30)
      if (months === 1) return '1 month ago'
      return `${months} months ago`
    },
    async submitWithStatus(status: 'draft' | 'open') {
      this.errors = {}
      this.errorMessage = ''
      this.loading = true

      const originalStatus = this.form.status
      this.form.status = status

      try {
        const project = await this.projectStore.createProject(this.form)
        this.$router.push('/projects/' + project.id)
      } catch (e: unknown) {
        const err = e as {
          response?: {
            status?: number
            data?: { errors?: Record<string, unknown>; message?: string }
          }
        }
        if (err?.response?.status === 422) {
          const errs = err.response.data?.errors ?? {}
          this.errors = mapValidationErrors(errs)
        } else {
          this.errorMessage = err?.response?.data?.message ?? 'Failed to create project.'
        }
      } finally {
        this.form.status = originalStatus
        this.loading = false
      }
    },
    async handleSubmit() {
      await this.submitWithStatus(this.form.status === 'draft' ? 'draft' : 'open')
    },
    async handleSaveDraft() {
      await this.submitWithStatus('draft')
      await this.loadDraftProjects()
    },
  },
})
</script>

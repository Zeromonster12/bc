<template>
  <AppLayout>
    <div class="mx-auto w-full max-w-7xl space-y-8 pb-8">
      <section class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-[#f8f6ff] via-[#f4f0ff] to-[#eef5ff] p-6 shadow-[0_20px_45px_rgba(30,27,53,0.08)] dark:border-slate-700/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Projects / Edit project</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">Refine your opportunity</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">
              Update project details and keep candidates aligned with your latest priorities.
            </p>
          </div>
        </div>
      </section>

      <BaseAlert
        v-if="errorMessage"
        type="error"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <div v-if="loadingInitial" class="space-y-4">
        <div class="h-12 w-full rounded-2xl bg-gray-100 animate-pulse dark:bg-slate-800" />
        <div class="h-80 rounded-3xl bg-gray-100 animate-pulse dark:bg-slate-800" />
      </div>

      <div v-else class="grid grid-cols-1 gap-8 xl:grid-cols-12">
        <div class="xl:col-span-8">
          <ProjectCreateForm
            :form="form"
            :tech-input="techInput"
            :errors="errors"
            :loading="saving"
            :show-save-draft="false"
            :allow-closed-status="true"
            submit-button-label="Save changes"
            @submit="handleSubmit"
            @cancel="$router.push('/projects/' + projectId)"
            @update-field="updateField"
            @update:techInput="techInput = $event"
            @add-tech="addTech"
            @remove-tech="removeTech"
          />
        </div>

        <aside class="space-y-6 xl:col-span-4">
          <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_30px_rgba(15,23,42,0.08)] dark:border-slate-700/70 dark:bg-slate-900/90">
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Live preview</h3>
            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
              <p class="text-base font-bold text-slate-900 dark:text-slate-100">{{ previewTitle }}</p>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ previewLocation }}</p>
              <p class="mt-4 line-clamp-4 text-sm text-slate-700 dark:text-slate-200">{{ previewDescription }}</p>

              <div class="mt-4 flex flex-wrap gap-2">
                <span
                  v-for="tag in previewTechStack"
                  :key="tag"
                  class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200"
                >
                  {{ tag }}
                </span>
              </div>

              <p class="mt-4 text-xs font-medium text-slate-500 dark:text-slate-400">
                Status: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ form.status }}</span>
                · Max students: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ form.max_students }}</span>
              </p>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { mapValidationErrors } from '@/services/shared/FormUtilsService'
import { addTechTag, createDefaultProjectForm } from '@/services/projects/ProjectFormService'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProjectCreateForm from '@/components/projects/ProjectCreateForm.vue'

interface EditableProject {
  id: number
  title?: string
  description?: string
  requirements?: string | null
  location?: string | null
  location_strategy?: 'remote' | 'onsite' | 'hybrid'
  industry?: string | null
  internship_duration?: string | null
  tech_stack?: string[]
  status?: string
  max_students?: number
  company?: {
    user_id?: number
  }
}

export default defineComponent({
  name: 'EditProjectView',
  components: { AppLayout, BaseAlert, ProjectCreateForm },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
    }
  },
  data() {
    return {
      form: createDefaultProjectForm(),
      techInput: '',
      errors: {} as Record<string, string>,
      errorMessage: '',
      loadingInitial: true,
      saving: false,
    }
  },
  computed: {
    projectId(): number {
      return Number(this.$route.params.id)
    },
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
  },
  async mounted() {
    if (!Number.isFinite(this.projectId) || this.projectId <= 0) {
      this.errorMessage = 'Invalid project id.'
      this.loadingInitial = false
      return
    }

    try {
      await this.projectStore.fetchProject(this.projectId)
      const project = this.projectStore.currentProject as EditableProject | null
      if (!project) {
        this.errorMessage = 'Project was not found.'
        return
      }

      const isOwnerCompany =
        this.auth.isCompany && project.company?.user_id === this.auth.user?.id
      if (!isOwnerCompany && !this.auth.isAdmin) {
        this.errorMessage = 'You are not allowed to edit this project.'
        return
      }

      this.form = {
        title: project.title ?? '',
        description: project.description ?? '',
        requirements: project.requirements ?? '',
        location: project.location ?? '',
        location_strategy:
          project.location_strategy === 'onsite' ||
          project.location_strategy === 'hybrid' ||
          project.location_strategy === 'remote'
            ? project.location_strategy
            : 'remote',
        industry: project.industry ?? 'Technology & Software',
        internship_duration: project.internship_duration ?? '3 Months (Summer)',
        tech_stack: Array.isArray(project.tech_stack) ? [...project.tech_stack] : [],
        status:
          project.status === 'draft' || project.status === 'open' || project.status === 'closed'
            ? project.status
            : 'draft',
        max_students:
          typeof project.max_students === 'number' && project.max_students > 0
            ? project.max_students
            : 1,
      }
    } catch {
      this.errorMessage = 'Could not load project for editing.'
    } finally {
      this.loadingInitial = false
    }
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
    async handleSubmit() {
      this.errors = {}
      this.errorMessage = ''
      this.saving = true
      try {
        const project = await this.projectStore.updateProject(this.projectId, this.form)
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
          this.errorMessage = err?.response?.data?.message ?? 'Failed to update project.'
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>

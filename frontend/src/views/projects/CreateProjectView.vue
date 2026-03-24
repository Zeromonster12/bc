<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">New project</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
          Fill in the details to publish a project for students.
        </p>
      </div>

      <BaseAlert
        v-if="errorMessage"
        type="error"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <ProjectCreateForm
        :form="form"
        :tech-input="techInput"
        :errors="errors"
        :loading="loading"
        @submit="handleSubmit"
        @cancel="$router.back()"
        @update-field="updateField"
        @update:techInput="techInput = $event"
        @add-tech="addTech"
        @remove-tech="removeTech"
      />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useProjectStore } from '@/stores/project'
import { mapValidationErrors } from '@/services/shared/FormUtilsService'
import { addTechTag, createDefaultProjectForm } from '@/services/projects/ProjectFormService'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProjectCreateForm from '@/components/projects/ProjectCreateForm.vue'

export default defineComponent({
  name: 'CreateProjectView',
  components: { AppLayout, BaseAlert, ProjectCreateForm },
  setup() {
    return { projectStore: useProjectStore() }
  },
  data() {
    return {
      form: createDefaultProjectForm(),
      techInput: '',
      errors: {} as Record<string, string>,
      errorMessage: '',
      loading: false,
    }
  },
  methods: {
    updateField(payload: {
      field: 'title' | 'description' | 'requirements' | 'status' | 'max_students' | 'deadline'
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
      this.loading = true
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
        this.loading = false
      }
    },
  },
})
</script>

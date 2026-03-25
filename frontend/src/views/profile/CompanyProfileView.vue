<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Company Profile</h1>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Build a detailed company profile so students can quickly understand your business, culture,
        and opportunities.
      </p>

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
        <div v-for="n in 4" :key="n" class="h-12 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
      </div>

      <CompanyProfileForm
        v-else
        :form="form"
        :errors="errors"
        :logo-preview="logoPreview"
        :logo-file-name="logoFileName"
        :saving="saving"
        @submit="handleSubmit"
        @logo-change="handleLogo"
        @update-field="updateField"
      />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import ProfileService from '@/services/profile/ProfileService'
import {
  type CompanyProfileForm,
  createDefaultCompanyProfileForm,
  hydrateCompanyProfileForm,
  toCompanyProfileFormData,
} from '@/services/profile/CompanyProfileFormService'
import { mapValidationErrors } from '@/services/shared/FormUtilsService'
import { resolveAssetUrl } from '@/services/core/url'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import CompanyProfileForm from '@/components/profile/CompanyProfileForm.vue'

export default defineComponent({
  name: 'CompanyProfileView',
  components: { AppLayout, BaseAlert, CompanyProfileForm },
  data() {
    return {
      form: createDefaultCompanyProfileForm(),
      logoFile: null as File | null,
      logoPreview: '',
      logoFileName: '',
      errors: {} as Record<string, string>,
      successMessage: '',
      errorMessage: '',
      loading: true,
      saving: false,
    }
  },
  async mounted() {
    try {
      const data = await ProfileService.getCompanyProfile()
      this.form = hydrateCompanyProfileForm(data)
      if (typeof data.logo_url === 'string' && data.logo_url) {
        this.logoPreview = resolveAssetUrl(data.logo_url)
      }
    } catch {
      // new profile
    } finally {
      this.loading = false
    }
  },
  methods: {
    updateField(payload: { field: keyof CompanyProfileForm; value: string }) {
      this.form[payload.field] = payload.value
    },
    handleLogo(e: Event) {
      const file = (e.target as HTMLInputElement).files?.[0]
      if (!file) {
        this.logoFile = null
        this.logoFileName = ''
        return
      }
      this.logoFile = file
      this.logoFileName = file.name
      this.logoPreview = URL.createObjectURL(file)
    },
    async handleSubmit() {
      this.errors = {}
      this.successMessage = ''
      this.errorMessage = ''
      this.saving = true
      try {
        const fd = toCompanyProfileFormData(this.form, this.logoFile)
        await ProfileService.updateCompanyProfile(fd)
        this.successMessage = 'Profile updated successfully.'
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
          this.errorMessage = err?.response?.data?.message ?? 'Failed to save profile.'
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>

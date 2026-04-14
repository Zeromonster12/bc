<template>
  <AppLayout>
    <div class="mx-auto max-w-5xl space-y-5">

      <BaseAlert
        v-if="showApprovalAlert"
        :type="approvalAlertType"
        :title="approvalAlertTitle"
        :message="approvalAlertMessage"
        :dismissible="false"
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

      <div v-if="loading" class="space-y-3">
        <div
          v-for="n in 4"
          :key="n"
          class="h-12 animate-pulse rounded-2xl bg-[#f1edf8] dark:bg-slate-800"
        />
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
import { useAuthStore } from '@/stores/auth'
import ProfileService from '@/services/profile/ProfileService'
import {
  type CompanyProfileForm as CompanyProfileFormModel,
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
  setup() {
    return {
      auth: useAuthStore(),
    }
  },
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
  computed: {
    companyApprovalStatus(): 'pending' | 'approved' | 'rejected' {
      const userStatus = this.auth.user?.company_verification_status
      if (userStatus === 'pending' || userStatus === 'approved' || userStatus === 'rejected') {
        return userStatus
      }

      const routeStatus = String(this.$route.query.approval ?? '').toLowerCase()
      if (routeStatus === 'pending' || routeStatus === 'approved' || routeStatus === 'rejected') {
        return routeStatus
      }

      return 'pending'
    },
    showApprovalAlert(): boolean {
      return this.companyApprovalStatus !== 'approved'
    },
    approvalAlertType(): 'warning' | 'error' {
      return this.companyApprovalStatus === 'rejected' ? 'error' : 'warning'
    },
    approvalAlertTitle(): string {
      return this.companyApprovalStatus === 'rejected'
        ? 'Company account rejected'
        : 'Company account pending approval'
    },
    approvalAlertMessage(): string {
      if (this.companyApprovalStatus === 'rejected') {
        return 'Your company account was not approved yet. Please update your company profile details and contact support for review.'
      }

      return 'Your company account is waiting for admin approval. You can complete your profile now, but project creation stays locked until approval.'
    },
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
    updateField(payload: { field: keyof CompanyProfileFormModel; value: string }) {
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

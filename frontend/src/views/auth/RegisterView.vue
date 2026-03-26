<template>
  <AuthLayout>
    <h2 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-slate-100">Create an account</h2>

    <BaseAlert
      v-if="errorMessage"
      type="error"
      class="mb-4"
      :message="errorMessage"
      dismissible
      @dismiss="errorMessage = ''"
    />

    <BaseAlert
      v-if="turnstileError"
      type="error"
      class="mb-4"
      :message="turnstileError"
      dismissible
      @dismiss="turnstileError = ''"
    />

    <form @submit.prevent="handleSubmit" novalidate>
      <div
        v-if="isCompanyFlow"
        class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800"
      >
        <p class="font-medium text-slate-900 dark:text-slate-100">
          Company registration: Step {{ currentStep }} of {{ totalSteps }}
        </p>
      </div>

      <div class="space-y-4">
        <div v-if="!isCompanyFlow || currentStep === 1" class="space-y-4">
          <AuthRoleSelector v-model="form.role" :options="roleOptions" :error="errors.role" />

          <BaseInput
            v-model="form.first_name"
            label="First name"
            type="text"
            autocomplete="given-name"
            :error="errors.first_name"
            required
          />
          <BaseInput
            v-model="form.last_name"
            label="Surname"
            type="text"
            autocomplete="family-name"
            :error="errors.last_name"
            required
          />
          <BaseInput
            v-model="form.email"
            label="Login email"
            type="email"
            autocomplete="email"
            :error="errors.email"
            required
          />
          <BaseInput
            v-model="form.password"
            label="Password"
            type="password"
            autocomplete="new-password"
            :error="errors.password"
            hint="Min. 8 characters, mixed case and numbers"
            required
          />
          <BaseInput
            v-model="form.password_confirmation"
            label="Confirm password"
            type="password"
            autocomplete="new-password"
            :error="errors.password_confirmation"
            required
          />
        </div>

        <div v-if="isCompanyFlow && currentStep === 2" class="space-y-4">
          <BaseInput v-model="form.business_name" label="Business name" type="text" :error="errors.business_name" required />
          <BaseInput
            v-model="form.billing_street"
            label="Billing street and number"
            type="text"
            :error="errors.billing_street"
            required
          />
          <BaseInput v-model="form.billing_city" label="Billing city" type="text" :error="errors.billing_city" required />
          <BaseInput
            v-model="form.billing_postal_code"
            label="Billing postal code"
            type="text"
            placeholder="821 01"
            :error="errors.billing_postal_code"
            required
          />
          <BaseInput v-model="form.ico" label="ICO" type="text" placeholder="12345678" :error="errors.ico" required />
          <BaseInput v-model="form.dic" label="DIC" type="text" placeholder="1234567890" :error="errors.dic" required />
          <BaseInput
            v-model="form.ic_dph"
            label="IC DPH (optional)"
            type="text"
            placeholder="SK1234567890"
            :error="errors.ic_dph"
          />
        </div>

        <div v-if="isCompanyFlow && currentStep === 3" class="space-y-4">
          <BaseInput
            v-model="form.contact_person_full_name"
            label="Contact person full name"
            type="text"
            :error="errors.contact_person_full_name"
            required
          />
          <BaseInput
            v-model="form.contact_email"
            label="Contact email"
            type="email"
            :error="errors.contact_email"
            required
          />
          <BaseInput
            v-model="form.phone"
            label="Contact phone"
            type="text"
            placeholder="+421 900 123 456"
            :error="errors.phone"
            required
          />

          <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
        </div>

        <TurnstileWidget v-if="!isCompanyFlow" ref="turnstileWidget" v-model="turnstileToken" />
      </div>

      <div class="mt-6 flex items-center gap-3">
        <BaseButton
          v-if="isCompanyFlow && currentStep > 1"
          type="button"
          variant="ghost"
          size="lg"
          @click="goToPreviousStep"
        >
          Back
        </BaseButton>
        <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full">
          {{ submitLabel }}
        </BaseButton>
      </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-slate-300">
      Already have an account?
      <RouterLink
        to="/login"
        class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
        >Sign in</RouterLink
      >
    </p>
  </AuthLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
  hasTurnstileToken,
  resolveErrorMessage,
  resolveValidationErrors,
} from '@/services/auth/AuthViewService'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'
import AuthRoleSelector from '@/components/auth/AuthRoleSelector.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

interface RoleOption {
  value: 'student' | 'company'
  label: string
}

export default defineComponent({
  name: 'RegisterView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert, TurnstileWidget, AuthRoleSelector },
  data() {
    return {
      form: {
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        business_name: '',
        billing_street: '',
        billing_city: '',
        billing_postal_code: '',
        ico: '',
        dic: '',
        ic_dph: '',
        contact_person_full_name: '',
        contact_email: '',
        phone: '',
        role: 'student' as 'student' | 'company',
      },
      roleOptions: [
        { value: 'student', label: 'Student' },
        { value: 'company', label: 'Company' },
      ] as RoleOption[],
      errors: {} as Record<string, string>,
      errorMessage: '',
      turnstileToken: '',
      turnstileError: '',
      loading: false,
      currentStep: 1,
    }
  },
  setup() {
    return { auth: useAuthStore() }
  },
  computed: {
    isCompanyFlow(): boolean {
      return this.form.role === 'company'
    },
    totalSteps(): number {
      return this.isCompanyFlow ? 3 : 1
    },
    isFinalStep(): boolean {
      return !this.isCompanyFlow || this.currentStep === this.totalSteps
    },
    submitLabel(): string {
      return this.isFinalStep ? 'Create account' : 'Next'
    },
  },
  watch: {
    'form.role'(nextRole: 'student' | 'company') {
      if (nextRole !== 'company') {
        this.currentStep = 1
      }
    },
  },
  methods: {
    goToPreviousStep() {
      this.errorMessage = ''
      this.turnstileError = ''
      this.currentStep = Math.max(1, this.currentStep - 1)
    },

    validateCurrentStep(): boolean {
      const nextErrors = { ...this.errors }

      const setRequiredError = (field: string, value: string, label: string) => {
        if (!String(value ?? '').trim()) {
          nextErrors[field] = `${label} is required.`
        } else {
          delete nextErrors[field]
        }
      }

      if (this.currentStep === 1) {
        setRequiredError('first_name', this.form.first_name, 'First name')
        setRequiredError('last_name', this.form.last_name, 'Surname')
        setRequiredError('email', this.form.email, 'Login email')
        setRequiredError('password', this.form.password, 'Password')
        setRequiredError('password_confirmation', this.form.password_confirmation, 'Confirm password')

        if (this.form.password && this.form.password_confirmation && this.form.password !== this.form.password_confirmation) {
          nextErrors.password_confirmation = 'Passwords do not match.'
        }
      }

      if (this.currentStep === 2) {
        setRequiredError('business_name', this.form.business_name, 'Business name')
        setRequiredError('billing_street', this.form.billing_street, 'Billing street and number')
        setRequiredError('billing_city', this.form.billing_city, 'Billing city')
        setRequiredError('billing_postal_code', this.form.billing_postal_code, 'Billing postal code')
        setRequiredError('ico', this.form.ico, 'ICO')
        setRequiredError('dic', this.form.dic, 'DIC')

        if (this.form.billing_postal_code && !/^\d{3}\s?\d{2}$/.test(this.form.billing_postal_code.trim())) {
          nextErrors.billing_postal_code = 'Use format 821 01.'
        }

        if (this.form.ico && !/^\d{8}$/.test(this.form.ico.trim())) {
          nextErrors.ico = 'ICO must have 8 digits.'
        }

        if (this.form.dic && !/^\d{10}$/.test(this.form.dic.trim())) {
          nextErrors.dic = 'DIC must have 10 digits.'
        }

        if (this.form.ic_dph && !/^SK\d{10}$/i.test(this.form.ic_dph.trim())) {
          nextErrors.ic_dph = 'IC DPH must have format SK1234567890.'
        }
      }

      if (this.currentStep === 3) {
        setRequiredError('contact_person_full_name', this.form.contact_person_full_name, 'Contact person full name')
        setRequiredError('contact_email', this.form.contact_email, 'Contact email')
        setRequiredError('phone', this.form.phone, 'Contact phone')

        if (
          this.form.contact_email
          && this.form.email
          && this.form.contact_email.trim().toLowerCase() === this.form.email.trim().toLowerCase()
        ) {
          nextErrors.contact_email = 'Contact email must be different from login email.'
        }
      }

      this.errors = nextErrors

      const stepFields: Record<number, string[]> = {
        1: ['first_name', 'last_name', 'email', 'password', 'password_confirmation'],
        2: ['business_name', 'billing_street', 'billing_city', 'billing_postal_code', 'ico', 'dic', 'ic_dph'],
        3: ['contact_person_full_name', 'contact_email', 'phone'],
      }

      return !stepFields[this.currentStep].some((field) => Boolean(this.errors[field]))
    },

    async handleSubmit() {
      this.errors = {}
      this.errorMessage = ''
      this.turnstileError = ''

      if (this.isCompanyFlow && !this.isFinalStep) {
        if (!this.validateCurrentStep()) {
          return
        }

        this.currentStep += 1
        return
      }

      if (this.isCompanyFlow && !this.validateCurrentStep()) {
        return
      }

      if (!hasTurnstileToken(this.turnstileToken)) {
        this.turnstileError = 'Please complete the captcha challenge.'
        return
      }

      this.loading = true
      try {
        const result = await this.auth.register({
          ...this.form,
          turnstile_token: this.turnstileToken,
        })
        this.$router.push({
          name: 'verify-email-code',
          query: { email: result.email ?? this.form.email },
        })
      } catch (e: unknown) {
        const err = e as { response?: { status?: number } }
        if (err?.response?.status === 422) {
          this.errors = resolveValidationErrors(e)
        } else {
          this.errorMessage = resolveErrorMessage(e, 'Registration failed. Please try again.')
        }
      } finally {
        ;(this.$refs.turnstileWidget as ResettableWidgetRef | undefined)?.reset?.()
        this.loading = false
      }
    },
  },
})
</script>

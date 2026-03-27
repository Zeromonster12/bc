<template>
  <AuthPageFrame prompt-text="Already have an account?" link-text="Log in" link-to="/login">
    <template #top-nav>
      <LandingTopBar />
    </template>

    <template #left>
      <AuthPromoPanel
        title-line1="Begin your"
        title-line2="curated"
        title-line3="professional"
        description="Join an elite network where academic excellence meets industry demand. Every achievement, a curated artifact of your journey."
        footer-lead="Joined by"
        footer-highlight="2,400+"
        footer-tail="top-tier scholars"
      />
    </template>

    <template #right>
      <section class="rounded-3xl border border-white/90 bg-white p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:rounded-4xl sm:p-10 lg:p-16">
        <h2 class="text-3xl font-semibold text-[#1d1f31] dark:text-slate-100 sm:text-4xl">Create Account</h2>
        <p class="mt-2 text-sm font-medium text-[#7d8195] dark:text-slate-400">Select your journey type to get started.</p>

        <BaseAlert
          v-if="errorMessage"
          type="error"
          class="mt-5"
          :message="errorMessage"
          dismissible
          @dismiss="errorMessage = ''"
        />

        <BaseAlert
          v-if="turnstileError"
          type="error"
          class="mt-4"
          :message="turnstileError"
          dismissible
          @dismiss="turnstileError = ''"
        />

        <form @submit.prevent="handleSubmit" novalidate class="mt-5 sm:mt-6">
          <div
            class="grid grid-cols-2 rounded-full border border-[#ded8ee] bg-[#e8e3f2] p-1 dark:border-slate-700 dark:bg-slate-800"
            role="tablist"
            aria-label="Account type"
          >
            <button
              type="button"
              class="rounded-full px-3 py-2.5 text-xs font-semibold transition sm:px-4 sm:text-sm"
              :class="form.role === 'student' ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
              @click="form.role = 'student'"
            >
              As a Student
            </button>
            <button
              type="button"
              class="rounded-full px-3 py-2.5 text-xs font-semibold transition sm:px-4 sm:text-sm"
              :class="form.role === 'company' ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
              @click="form.role = 'company'"
            >
              As a Company
            </button>
          </div>
          <p v-if="errors.role" class="mt-2 text-xs text-red-600">{{ errors.role }}</p>

          <div v-if="isCompanyFlow" class="mt-5">
            <div class="flex items-center justify-center gap-2">
              <template v-for="step in [1, 2, 3]" :key="step">
                <span
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-sm font-bold"
                  :class="
                    step === currentStep
                      ? 'border-[#4b35cb] bg-[#4b35cb] text-white'
                      : step < currentStep
                        ? 'border-[#4b35cb]/50 bg-[#ebe5ff] text-[#4b35cb]'
                        : 'border-[#d7d3e7] bg-white text-[#8b90a7] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400'
                  "
                >
                  {{ step }}
                </span>
                <span
                  v-if="step < 3"
                  class="h-0.5 w-6"
                  :class="step < currentStep ? 'bg-[#4b35cb]/45' : 'bg-[#d7d3e7] dark:bg-slate-700'"
                />
              </template>
            </div>
            <p class="mt-2 text-center text-[11px] font-semibold tracking-[0.04em] text-[#4f536f] dark:text-slate-400 sm:text-xs sm:tracking-[0.06em]">
              {{
                currentStep === 1
                  ? 'Step 1: Account details'
                  : currentStep === 2
                    ? 'Step 2: Company billing details'
                    : 'Step 3: Contact person details'
              }}
            </p>
          </div>

          <div class="mt-6 space-y-4">
            <div v-if="!isCompanyFlow || currentStep === 1" class="space-y-4">
              <div class="grid gap-4 sm:grid-cols-2">
                <BaseInput
                  v-model="form.first_name"
                  label="FIRST NAME"
                  label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                  type="text"
                  placeholder="Eleanor"
                  autocomplete="given-name"
                  :error="errors.first_name"
                  required
                />
                <BaseInput
                  v-model="form.last_name"
                  label="SURNAME"
                  label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                  type="text"
                  placeholder="Vance"
                  autocomplete="family-name"
                  :error="errors.last_name"
                  required
                />
              </div>
              <BaseInput
                v-model="form.email"
                label="EMAIL"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="email"
                placeholder="vance.e@university.edu"
                autocomplete="email"
                :error="errors.email"
                required
              />
              <div class="grid gap-4 sm:grid-cols-2">
                <BaseInput
                  v-model="form.password"
                  label="PASSWORD"
                  label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                  type="password"
                  :allow-password-toggle="true"
                  autocomplete="new-password"
                  :error="errors.password"
                  required
                />
                <BaseInput
                  v-model="form.password_confirmation"
                  label="CONFIRM"
                  label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                  type="password"
                  :allow-password-toggle="true"
                  autocomplete="new-password"
                  :error="errors.password_confirmation"
                  required
                />
              </div>
            </div>

            <div v-if="isCompanyFlow && currentStep === 2" class="space-y-4">
              <BaseInput
                v-model="form.business_name"
                label="BUSINESS NAME"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                :error="errors.business_name"
                required
              />
              <BaseInput
                v-model="form.billing_street"
                label="BILLING STREET AND NUMBER"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                :error="errors.billing_street"
                required
              />
              <BaseInput
                v-model="form.billing_city"
                label="BILLING CITY"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                :error="errors.billing_city"
                required
              />
              <BaseInput
                v-model="form.billing_postal_code"
                label="BILLING POSTAL CODE"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                placeholder="821 01"
                :error="errors.billing_postal_code"
                required
              />
              <div class="grid gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.ico" label="ICO" label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300" type="text" placeholder="12345678" :error="errors.ico" required />
                <BaseInput v-model="form.dic" label="DIC" label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300" type="text" placeholder="1234567890" :error="errors.dic" required />
              </div>
              <BaseInput
                v-model="form.ic_dph"
                label="IC DPH (OPTIONAL)"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                placeholder="SK1234567890"
                :error="errors.ic_dph"
              />
            </div>

            <div v-if="isCompanyFlow && currentStep === 3" class="space-y-4">
              <BaseInput
                v-model="form.contact_person_full_name"
                label="CONTACT PERSON FULL NAME (DEFAULTS TO ACCOUNT NAME)"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                :error="errors.contact_person_full_name"
                required
              />
              <BaseInput
                v-model="form.contact_email"
                label="CONTACT EMAIL (CAN BE SAME AS LOGIN EMAIL)"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="email"
                :error="errors.contact_email"
                required
              />
              <BaseInput
                v-model="form.phone"
                label="CONTACT PHONE"
                label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
                type="text"
                placeholder="+421 900 123 456"
                :error="errors.phone"
                required
              />

              <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" class="pt-1" />
            </div>

            <TurnstileWidget v-if="!isCompanyFlow" ref="turnstileWidget" v-model="turnstileToken" class="pt-1" />
          </div>

          <label class="mt-4 flex items-start gap-2 text-xs text-[#676b84] dark:text-slate-400">
            <input type="checkbox" class="mt-0.5 h-4 w-4 rounded border-[#cdc7de] text-[#4b35cb] focus:ring-[#4b35cb] dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-400" />
            <span>I agree to the <span class="font-semibold text-[#4b35cb] dark:text-indigo-300">Terms of Service</span> and <span class="font-semibold text-[#4b35cb] dark:text-indigo-300">Privacy Policy</span>.</span>
          </label>

          <div class="mt-5 flex items-center gap-3">
            <BaseButton
              v-if="isCompanyFlow && currentStep > 1"
              type="button"
              variant="ghost"
              size="lg"
              @click="goToPreviousStep"
            >
              Back
            </BaseButton>

            <button
              type="submit"
              :disabled="loading"
              class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-6 py-3 text-base font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
                <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" class="opacity-90" />
              </svg>
              {{ submitLabel }}
            </button>
          </div>
        </form>

        <div class="mt-6 flex items-center gap-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#a3a7bb] dark:text-slate-500 sm:mt-7 sm:text-[11px]">
          <span class="h-px flex-1 bg-[#ebebf1] dark:bg-slate-700" />
          or sign up with
          <span class="h-px flex-1 bg-[#ebebf1] dark:bg-slate-700" />
        </div>

        <div class="mt-4">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-[#ebeaf2] bg-white px-4 py-3 text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click="handleGoogleLogin"
          >
            <img :src="googleLogo" alt="Google logo" class="h-4 w-4" />
            Google
          </button>
        </div>

        <p class="mt-5 text-center text-sm text-[#686d84] dark:text-slate-400 sm:mt-6">
          Already have an account?
          <RouterLink to="/login" class="font-semibold text-[#4b35cb] hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200">Sign in</RouterLink>
        </p>
      </section>
    </template>
  </AuthPageFrame>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthService from '@/services/auth/AuthService'
import googleLogo from '@/assets/icons/google-logo.svg'
import {
  hasTurnstileToken,
  resolveErrorMessage,
  resolveValidationErrors,
} from '@/services/auth/AuthViewService'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'
import AuthPageFrame from '@/components/auth/AuthPageFrame.vue'
import AuthPromoPanel from '@/components/auth/AuthPromoPanel.vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

interface RoleOption {
  value: 'student' | 'company'
  label: string
}

export default defineComponent({
  name: 'RegisterView',
  components: {
    BaseInput,
    BaseButton,
    BaseAlert,
    TurnstileWidget,
    AuthPageFrame,
    AuthPromoPanel,
    LandingTopBar,
  },
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
      googleLogo,
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
    ensureCompanyContactDefaults() {
      const fullName = `${this.form.first_name} ${this.form.last_name}`.trim()
      if (!this.form.contact_person_full_name.trim() && fullName) {
        this.form.contact_person_full_name = fullName
      }

      if (!this.form.contact_email.trim() && this.form.email.trim()) {
        this.form.contact_email = this.form.email.trim()
      }
    },

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
      }

      this.errors = nextErrors

      const stepFields: Record<number, string[]> = {
        1: ['first_name', 'last_name', 'email', 'password', 'password_confirmation'],
        2: ['business_name', 'billing_street', 'billing_city', 'billing_postal_code', 'ico', 'dic', 'ic_dph'],
        3: ['contact_person_full_name', 'contact_email', 'phone'],
      }

      return !(stepFields[this.currentStep] ?? []).some((field) => Boolean(this.errors[field]))
    },

    async handleSubmit() {
      this.errors = {}
      this.errorMessage = ''
      this.turnstileError = ''

      if (this.isCompanyFlow && !this.isFinalStep) {
        if (!this.validateCurrentStep()) {
          return
        }

        if (this.currentStep === 2) {
          this.ensureCompanyContactDefaults()
        }

        this.currentStep += 1
        return
      }

      if (this.isCompanyFlow) {
        this.ensureCompanyContactDefaults()
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
    async handleGoogleLogin() {
      this.errorMessage = ''

      try {
        const response = await AuthService.getGoogleOAuthRedirectUrl()
        window.location.href = response.url
      } catch (e: unknown) {
        this.errorMessage = resolveErrorMessage(e, 'Google sign-in is unavailable right now.')
      }
    },
  },
})
</script>

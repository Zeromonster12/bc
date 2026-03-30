<template>
  <AuthPageFrame prompt-text="Don't have an account?" link-text="Sign up" link-to="/register">
    <template #top-nav>
      <LandingTopBar />
    </template>

    <template #left>
      <AuthPromoPanel
        title-line1="Continue your"
        title-line2="curated"
        title-line3="professional"
        description="Pick up where you left off. Access opportunities, projects and conversations in one focused student workspace."
        footer-lead="Trusted by"
        footer-highlight="2,400+"
        footer-tail="active users"
      />
    </template>

    <template #right>
      <section class="rounded-3xl border border-white/90 bg-white p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:rounded-4xl sm:p-10 lg:p-16">
        <h2 class="text-3xl font-semibold text-[#1d1f31] dark:text-slate-100 sm:text-4xl">Verify your email</h2>
        <p class="mt-2 text-sm font-medium text-[#7d8195] dark:text-slate-400">
          We sent a 6-digit code to <strong>{{ email }}</strong
          >. Enter it below to unlock your workspace.
        </p>

        <BaseAlert
          v-if="successMessage"
          type="success"
          class="mt-5"
          :message="successMessage"
          :dismissible="false"
        />

        <BaseAlert
          v-if="errorMessage"
          type="error"
          class="mt-4"
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

        <form v-if="!verificationCompleted" @submit.prevent="handleVerify" novalidate class="mt-5 sm:mt-6">
          <div>
            <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.08em] text-[#2f334f] dark:text-slate-300">
              Verification code
            </p>
            <div class="grid grid-cols-6 gap-2 sm:gap-3">
              <input
                v-for="(_, index) in codeDigits"
                :key="`code-digit-${index}`"
                ref="otpInputs"
                :value="codeDigits[index]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                class="h-12 w-full rounded-2xl border border-[#ebeaf2] bg-[#f8f7fc] text-center text-lg font-semibold text-[#23253a] transition focus:border-[#8e84db] focus:outline-none focus:ring-2 focus:ring-[#c9c3ef] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/30"
                :aria-label="`Verification code digit ${index + 1}`"
                @input="handleDigitInput(index, ($event.target as HTMLInputElement).value)"
                @keydown="handleDigitKeydown(index, $event)"
                @focus="handleDigitFocus(index)"
                @paste="handleDigitPaste($event)"
              />
            </div>
            <p v-if="codeError" class="mt-2 text-xs text-red-600">{{ codeError }}</p>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-6 py-3 text-base font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
              <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" class="opacity-90" />
            </svg>
            Verify email
          </button>
        </form>

        <div v-if="!verificationCompleted" class="mt-4 text-center">
          <div class="mb-3 text-left">
            <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" class="pt-2" />
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#4b35cb] transition hover:text-[#3d28b2] disabled:cursor-not-allowed disabled:opacity-60 dark:text-indigo-300 dark:hover:text-indigo-200"
            :disabled="resendLoading"
            @click="handleResend"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 12a9 9 0 1 1-2.64-6.36" />
              <path d="M21 3v6h-6" />
            </svg>
            <span>{{ resendLoading ? 'Sending...' : 'Resend code' }}</span>
          </button>
        </div>

        <p class="mt-5 text-center text-sm text-[#686d84] dark:text-slate-400 sm:mt-6">
          <RouterLink to="/login" class="font-semibold text-[#4b35cb] hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200">Back to sign in</RouterLink>
        </p>
      </section>
    </template>
  </AuthPageFrame>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AuthService from '@/services/auth/AuthService'
import {
  hasTurnstileToken,
  resolveErrorMessage,
  resolveSingleFieldError,
} from '@/services/auth/AuthViewService'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'
import AuthPageFrame from '@/components/auth/AuthPageFrame.vue'
import AuthPromoPanel from '@/components/auth/AuthPromoPanel.vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

export default defineComponent({
  name: 'VerifyEmailCodeView',
  components: { BaseAlert, TurnstileWidget, AuthPageFrame, AuthPromoPanel, LandingTopBar },
  data() {
    return {
      email: (this.$route.query.email as string) ?? '',
      codeDigits: Array.from({ length: 6 }, () => ''),
      codeError: '',
      errorMessage: '',
      turnstileError: '',
      successMessage: '',
      verificationCompleted: false,
      turnstileToken: '',
      loading: false,
      resendLoading: false,
    }
  },
  mounted() {
    if (!this.email) {
      this.$router.replace({ name: 'register' })
      return
    }

    this.$nextTick(() => this.focusDigit(0))
  },
  methods: {
    getOtpInputs(): HTMLInputElement[] {
      const refs = this.$refs.otpInputs
      if (!refs) {
        return []
      }

      return Array.isArray(refs) ? (refs as HTMLInputElement[]) : [refs as HTMLInputElement]
    },

    focusDigit(index: number) {
      const inputs = this.getOtpInputs()
      const target = inputs[index]
      if (!target) {
        return
      }

      target.focus()
      target.select()
    },

    applyPastedCode(rawValue: string) {
      const digits = rawValue.replace(/\D/g, '').slice(0, this.codeDigits.length).split('')
      if (!digits.length) {
        return
      }

      this.codeDigits = this.codeDigits.map((_, index) => digits[index] ?? '')
      const targetIndex = Math.min(digits.length, this.codeDigits.length - 1)
      this.$nextTick(() => this.focusDigit(targetIndex))
    },

    handleDigitInput(index: number, rawValue: string) {
      const sanitized = rawValue.replace(/\D/g, '')
      this.codeError = ''

      if (!sanitized) {
        this.codeDigits[index] = ''
        return
      }

      if (sanitized.length > 1) {
        this.applyPastedCode(sanitized)
        return
      }

      this.codeDigits[index] = sanitized

      if (index < this.codeDigits.length - 1) {
        this.$nextTick(() => this.focusDigit(index + 1))
      }
    },

    handleDigitKeydown(index: number, event: KeyboardEvent) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault()
        if (index > 0) {
          this.focusDigit(index - 1)
        }
        return
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault()
        if (index < this.codeDigits.length - 1) {
          this.focusDigit(index + 1)
        }
        return
      }

      if (event.key === 'Backspace') {
        event.preventDefault()
        if (this.codeDigits[index]) {
          this.codeDigits[index] = ''
          return
        }

        if (index > 0) {
          this.codeDigits[index - 1] = ''
          this.$nextTick(() => this.focusDigit(index - 1))
        }
        return
      }

      if (event.key === 'Delete') {
        event.preventDefault()
        this.codeDigits[index] = ''
        return
      }

      if (event.key === 'Tab' || event.key === 'Shift') {
        return
      }

      if (!/^\d$/.test(event.key)) {
        event.preventDefault()
      }
    },

    handleDigitFocus(index: number) {
      if (!this.codeDigits[index]) {
        return
      }

      this.$nextTick(() => this.focusDigit(index))
    },

    handleDigitPaste(event: ClipboardEvent) {
      event.preventDefault()
      const pastedText = event.clipboardData?.getData('text') ?? ''
      this.applyPastedCode(pastedText)
      this.codeError = ''
    },

    async handleVerify() {
      const code = this.codeDigits.join('').trim()
      if (code.length !== this.codeDigits.length) {
        this.codeError = 'Enter the 6-digit verification code.'
        const firstEmptyIndex = this.codeDigits.findIndex((digit) => !digit)
        this.$nextTick(() => this.focusDigit(firstEmptyIndex >= 0 ? firstEmptyIndex : 0))
        return
      }

      this.codeError = ''
      this.errorMessage = ''
      this.loading = true

      try {
        const result = await AuthService.verifyEmailCode({
          email: this.email,
          code,
        })

        this.successMessage = result?.message ?? 'Email verified. You can now continue.'
        this.verificationCompleted = true

        const shouldOpenCompanyProfile = result?.next_step === 'complete_company_profile'
        setTimeout(() => {
          if (shouldOpenCompanyProfile) {
            this.$router.push({
              name: 'login',
              query: { redirect: '/profile/company' },
            })
            return
          }

          this.$router.push({ name: 'login' })
        }, 1200)
      } catch (e: unknown) {
        const err = e as { response?: { status?: number } }
        if (err?.response?.status === 422) {
          this.codeError = resolveSingleFieldError(e, 'code')
          this.errorMessage = resolveErrorMessage(e, 'Invalid or expired code.')
        } else {
          this.errorMessage = resolveErrorMessage(e, 'Verification failed. Please try again.')
        }
      } finally {
        this.loading = false
      }
    },

    async handleResend() {
      this.errorMessage = ''
      this.codeError = ''
      this.turnstileError = ''

      if (!hasTurnstileToken(this.turnstileToken)) {
        this.turnstileError = 'Please complete the captcha challenge before resending.'
        return
      }

      this.resendLoading = true

      try {
        const result = await AuthService.resendVerificationEmail({
          email: this.email,
          turnstile_token: this.turnstileToken,
        })
        this.successMessage = result?.message ?? 'Verification code sent.'
        this.codeDigits = Array.from({ length: 6 }, () => '')
        this.$nextTick(() => this.focusDigit(0))
      } catch (e: unknown) {
        const err = e as { response?: { status?: number } }
        if (err?.response?.status === 422) {
          this.turnstileError = resolveSingleFieldError(e, 'turnstile_token')
        }
        this.errorMessage = resolveErrorMessage(e, 'Unable to resend code right now.')
      } finally {
        ;(this.$refs.turnstileWidget as ResettableWidgetRef | undefined)?.reset?.()
        this.resendLoading = false
      }
    },
  },
})
</script>

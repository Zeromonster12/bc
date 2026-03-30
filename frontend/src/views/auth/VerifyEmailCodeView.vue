<template>
  <AuthLayout>
    <h2 class="mb-2 text-center text-2xl font-bold text-slate-900 dark:text-slate-100">Verify your email</h2>
    <p class="mb-6 text-center text-sm text-slate-500 dark:text-slate-400">
      We sent a 6-digit code to <strong>{{ email }}</strong
      >. Enter it below to unlock your workspace.
    </p>

    <BaseAlert
      v-if="successMessage"
      type="success"
      class="mb-4"
      :message="successMessage"
      :dismissible="false"
    />

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

    <form v-if="!verificationCompleted" @submit.prevent="handleVerify" novalidate>
      <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
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
            class="h-12 w-full rounded-xl border border-amber-100 bg-white text-center text-lg font-semibold text-slate-900 shadow-sm transition focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-amber-800/60 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-amber-500 dark:focus:ring-amber-500/40"
            :aria-label="`Verification code digit ${index + 1}`"
            @input="handleDigitInput(index, ($event.target as HTMLInputElement).value)"
            @keydown="handleDigitKeydown(index, $event)"
            @focus="handleDigitFocus(index)"
            @paste="handleDigitPaste($event)"
          />
        </div>
        <p v-if="codeError" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ codeError }}</p>
      </div>

      <BaseButton
        type="submit"
        variant="primary"
        size="lg"
        :loading="loading"
        class="mt-6 w-full rounded-xl! bg-amber-300! font-semibold! text-amber-900! hover:bg-amber-400! focus:ring-amber-300! dark:bg-amber-400! dark:text-amber-950! dark:hover:bg-amber-300!"
      >
        Verify email
      </BaseButton>
    </form>

    <div v-if="!verificationCompleted" class="mt-4 text-center">
      <div class="mb-3 text-left">
        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
      </div>
      <button
        type="button"
        class="mx-auto inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600 transition hover:text-amber-700 disabled:cursor-not-allowed disabled:opacity-60 dark:text-amber-300 dark:hover:text-amber-200"
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

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-slate-300">
      <RouterLink
        to="/login"
        class="font-medium text-amber-700 hover:text-amber-800 dark:text-amber-300 dark:hover:text-amber-200"
      >
        Back to sign in
      </RouterLink>
    </p>
  </AuthLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AuthService from '@/services/auth/AuthService'
import {
  hasTurnstileToken,
  resolveErrorMessage,
  resolveSingleFieldError,
} from '@/services/auth/AuthViewService'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

export default defineComponent({
  name: 'VerifyEmailCodeView',
  components: { AuthLayout, BaseButton, BaseAlert, TurnstileWidget },
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

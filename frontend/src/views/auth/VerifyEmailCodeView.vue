<template>
  <AuthLayout>
    <h2 class="mb-2 text-center text-2xl font-bold text-gray-900 dark:text-slate-100">Verify your email</h2>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-slate-400">
      Enter the 6-digit code sent to <strong>{{ email }}</strong
      >.
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
      <BaseInput
        v-model="code"
        label="Verification code"
        type="text"
        autocomplete="one-time-code"
        :error="codeError"
        placeholder="123456"
        required
      />

      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Verify email
      </BaseButton>
    </form>

    <div v-if="!verificationCompleted" class="mt-4 text-center">
      <div class="mb-3 text-left">
        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
      </div>
      <button
        type="button"
        class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
        :disabled="resendLoading"
        @click="handleResend"
      >
        {{ resendLoading ? 'Sending...' : 'Resend code' }}
      </button>
    </div>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-slate-300">
      <RouterLink
        to="/login"
        class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
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
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

export default defineComponent({
  name: 'VerifyEmailCodeView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert, TurnstileWidget },
  data() {
    return {
      email: (this.$route.query.email as string) ?? '',
      code: '',
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
    }
  },
  methods: {
    async handleVerify() {
      this.codeError = ''
      this.errorMessage = ''
      this.loading = true

      try {
        const result = await AuthService.verifyEmailCode({
          email: this.email,
          code: this.code.trim(),
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

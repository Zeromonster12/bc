<template>
  <AuthLayout>
    <h2 class="text-2xl font-bold text-gray-900 mb-2 text-center">Forgot password?</h2>
    <p class="text-sm text-gray-500 text-center mb-6">
      Enter your email and we'll send you a reset link.
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

    <form v-if="!successMessage" @submit.prevent="handleSubmit" novalidate>
      <BaseInput
        v-model="email"
        label="Email address"
        type="email"
        autocomplete="email"
        :error="emailError"
        required
      />
      <div class="mt-4">
        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
      </div>
      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Send reset link
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      <RouterLink to="/login" class="text-indigo-600 hover:text-indigo-500 font-medium">
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
  name: 'ForgotPasswordView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert, TurnstileWidget },
  data() {
    return {
      email: '',
      emailError: '',
      successMessage: '',
      errorMessage: '',
      turnstileToken: '',
      turnstileError: '',
      loading: false,
    }
  },
  methods: {
    async handleSubmit() {
      this.emailError = ''
      this.errorMessage = ''
      this.turnstileError = ''

      if (!hasTurnstileToken(this.turnstileToken)) {
        this.turnstileError = 'Please complete the captcha challenge.'
        return
      }

      this.loading = true
      try {
        await AuthService.forgotPassword({
          email: this.email,
          turnstile_token: this.turnstileToken,
        })
        this.successMessage = 'A password reset link has been sent to your email address.'
      } catch (e: unknown) {
        if (e?.response?.status === 422) {
          this.emailError = resolveSingleFieldError(e, 'email')
          this.turnstileError = resolveSingleFieldError(e, 'turnstile_token')
        } else {
          this.errorMessage = resolveErrorMessage(e, 'Request failed. Please try again.')
        }
      } finally {
        ;(this.$refs.turnstileWidget as ResettableWidgetRef | undefined)?.reset?.()
        this.loading = false
      }
    },
  },
})
</script>

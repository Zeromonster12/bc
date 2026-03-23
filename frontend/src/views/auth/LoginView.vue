<template>
  <AuthLayout>
    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Sign in to your account</h2>

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

    <AuthGoogleButton @click="handleGoogleLogin" />

    <div class="mb-4 flex items-center gap-3 text-xs text-slate-400">
      <span class="h-px flex-1 bg-slate-200"></span>
      <span>or use email and password</span>
      <span class="h-px flex-1 bg-slate-200"></span>
    </div>

    <form @submit.prevent="handleLogin" novalidate>
      <div class="space-y-4">
        <BaseInput
          v-model="form.email"
          label="Email address"
          type="email"
          autocomplete="email"
          :error="errors.email"
          required
        />
        <BaseInput
          v-model="form.password"
          label="Password"
          type="password"
          autocomplete="current-password"
          :error="errors.password"
          required
        />

        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
      </div>

      <div class="flex items-center justify-end mt-3">
        <RouterLink to="/forgot-password" class="text-sm text-indigo-600 hover:text-indigo-500">
          Forgot your password?
        </RouterLink>
      </div>

      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Sign in
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      Don't have an account?
      <RouterLink to="/register" class="text-indigo-600 hover:text-indigo-500 font-medium"
        >Sign up</RouterLink
      >
    </p>
  </AuthLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthService from '@/services/auth/AuthService'
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
import AuthGoogleButton from '@/components/auth/AuthGoogleButton.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

export default defineComponent({
  name: 'LoginView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert, TurnstileWidget, AuthGoogleButton },
  data() {
    return {
      form: { email: '', password: '' },
      errors: {} as Record<string, string>,
      errorMessage: '',
      turnstileToken: '',
      turnstileError: '',
      loading: false,
    }
  },
  setup() {
    return { auth: useAuthStore() }
  },
  methods: {
    async handleLogin() {
      this.errors = {}
      this.errorMessage = ''
      this.turnstileError = ''

      if (!hasTurnstileToken(this.turnstileToken)) {
        this.turnstileError = 'Please complete the captcha challenge.'
        return
      }

      this.loading = true
      try {
        await this.auth.login({
          ...this.form,
          turnstile_token: this.turnstileToken,
        })
        const redirect = (this.$route.query.redirect as string) || '/dashboard'
        this.$router.push(redirect)
      } catch (e: unknown) {
        if (e?.response?.status === 422) {
          this.errors = resolveValidationErrors(e)
        } else if (e?.response?.status === 403) {
          this.$router.push({
            name: 'verify-email-code',
            query: { email: this.form.email },
          })
        } else {
          this.errorMessage = resolveErrorMessage(e, 'Login failed. Please try again.')
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

<template>
  <AuthLayout :show-side-panel="false" :show-top-nav="true">
    <h2 class="mb-6 mt-6 text-center text-2xl font-bold text-gray-900 dark:text-slate-100">Sign in to your account</h2>

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

        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" class="my-2 pt-5 sm:my-3" />
      </div>

      <div class="flex items-center justify-end mt-3">
        <RouterLink to="/forgot-password" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
          Forgot your password?
        </RouterLink>
      </div>

      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Sign in
      </BaseButton>
    </form>

    <div class="mt-5 mb-4 flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
      <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
      <span>or continue with Google</span>
      <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
    </div>

    <AuthGoogleButton @click="handleGoogleLogin" />

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-slate-300">
      Don't have an account?
      <RouterLink
        to="/register"
        class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
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
        const err = e as { response?: { status?: number } }
        if (err?.response?.status === 422) {
          this.errors = resolveValidationErrors(e)
        } else if (err?.response?.status === 403) {
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

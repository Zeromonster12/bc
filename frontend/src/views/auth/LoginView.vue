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
        <h2 class="text-3xl font-semibold text-[#1d1f31] dark:text-slate-100 sm:text-4xl">Welcome Back</h2>
        <p class="mt-2 text-sm font-medium text-[#7d8195] dark:text-slate-400">Sign in to continue your journey.</p>

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

        <form @submit.prevent="handleLogin" novalidate class="mt-5 sm:mt-6">
          <div class="space-y-4">
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
            <BaseInput
              v-model="form.password"
              label="PASSWORD"
              label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
              type="password"
              :allow-password-toggle="true"
              autocomplete="current-password"
              :error="errors.password"
              required
            />

            <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" class="pt-2" />
          </div>

          <div class="mt-3 flex items-center justify-end">
            <RouterLink to="/forgot-password" class="text-sm font-medium text-[#4b35cb] hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200">
              Forgot your password?
            </RouterLink>
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
            Sign in
          </button>
        </form>

        <div class="mt-6 flex items-center gap-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#a3a7bb] dark:text-slate-500 sm:mt-7 sm:text-[11px]">
          <span class="h-px flex-1 bg-[#ebebf1] dark:bg-slate-700" />
          or sign in with
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
          Don't have an account?
          <RouterLink to="/register" class="font-semibold text-[#4b35cb] hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200">Sign up</RouterLink>
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
import BaseAlert from '@/components/ui/BaseAlert.vue'
import TurnstileWidget from '@/components/ui/TurnstileWidget.vue'
import AuthPageFrame from '@/components/auth/AuthPageFrame.vue'
import AuthPromoPanel from '@/components/auth/AuthPromoPanel.vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

export default defineComponent({
  name: 'LoginView',
  components: { BaseInput, BaseAlert, TurnstileWidget, AuthPageFrame, AuthPromoPanel, LandingTopBar },
  data() {
    return {
      form: { email: '', password: '' },
      errors: {} as Record<string, string>,
      googleLogo,
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

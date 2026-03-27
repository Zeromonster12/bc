<template>
  <AuthPageFrame prompt-text="Remember your password?" link-text="Sign in" link-to="/login">
    <template #top-nav>
      <LandingTopBar />
    </template>

    <template #left>
      <AuthPromoPanel
        title-line1="Set your"
        title-line2="new"
        title-line3="password"
        description="Use a strong password so your account stays protected while you continue working with projects and teams."
        footer-lead="Protected by"
        footer-highlight="secure"
        footer-tail="authentication"
      />
    </template>

    <template #right>
      <section class="rounded-3xl border border-white/90 bg-white p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:rounded-4xl sm:p-10 lg:p-16">
        <h2 class="text-3xl font-semibold text-[#1d1f31] dark:text-slate-100 sm:text-4xl">Reset Password</h2>
        <p class="mt-2 text-sm font-medium text-[#7d8195] dark:text-slate-400">
          Enter your new password for <strong>{{ email }}</strong>.
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
          class="mt-5"
          :message="errorMessage"
          dismissible
          @dismiss="errorMessage = ''"
        />

        <form v-if="!successMessage" @submit.prevent="handleSubmit" novalidate class="mt-5 sm:mt-6">
          <div class="space-y-4">
            <BaseInput
              v-model="form.email"
              label="EMAIL"
              label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
              type="email"
              autocomplete="email"
              :error="errors.email"
              required
            />

            <BaseInput
              v-model="form.password"
              label="NEW PASSWORD"
              label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
              type="password"
              :allow-password-toggle="true"
              autocomplete="new-password"
              :error="errors.password"
              required
            />

            <BaseInput
              v-model="form.password_confirmation"
              label="CONFIRM NEW PASSWORD"
              label-class="text-[11px] font-bold tracking-[0.08em] text-[#2f334f] dark:text-slate-300"
              type="password"
              :allow-password-toggle="true"
              autocomplete="new-password"
              :error="errors.password_confirmation"
              required
            />
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
            Save new password
          </button>
        </form>

        <p class="mt-5 text-center text-sm text-[#686d84] dark:text-slate-400 sm:mt-6">
          Back to
          <RouterLink to="/login" class="font-semibold text-[#4b35cb] hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200">
            sign in
          </RouterLink>
        </p>
      </section>
    </template>
  </AuthPageFrame>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AuthService from '@/services/auth/AuthService'
import { resolveErrorMessage, resolveValidationErrors } from '@/services/auth/AuthViewService'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import AuthPageFrame from '@/components/auth/AuthPageFrame.vue'
import AuthPromoPanel from '@/components/auth/AuthPromoPanel.vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'

export default defineComponent({
  name: 'ResetPasswordView',
  components: { BaseInput, BaseAlert, AuthPageFrame, AuthPromoPanel, LandingTopBar },
  data() {
    return {
      form: {
        token: String(this.$route.params.token ?? ''),
        email: String(this.$route.query.email ?? ''),
        password: '',
        password_confirmation: '',
      },
      errors: {} as Record<string, string>,
      errorMessage: '',
      successMessage: '',
      loading: false,
    }
  },
  computed: {
    email(): string {
      return this.form.email || 'your account'
    },
  },
  mounted() {
    if (!this.form.token) {
      this.errorMessage = 'Reset token is missing from the link.'
    }
  },
  methods: {
    async handleSubmit() {
      this.errors = {}
      this.errorMessage = ''

      if (!this.form.token) {
        this.errorMessage = 'Reset token is missing from the link.'
        return
      }

      this.loading = true
      try {
        await AuthService.resetPassword(this.form)
        this.successMessage = 'Password was reset successfully. Redirecting to sign in...'
        setTimeout(() => {
          this.$router.push({ name: 'login' })
        }, 1200)
      } catch (e: unknown) {
        const err = e as { response?: { status?: number } }
        if (err?.response?.status === 422) {
          this.errors = resolveValidationErrors(e)
        } else {
          this.errorMessage = resolveErrorMessage(
            e,
            'Unable to reset password. Please request a new reset link.',
          )
        }
      } finally {
        this.loading = false
      }
    },
  },
})
</script>

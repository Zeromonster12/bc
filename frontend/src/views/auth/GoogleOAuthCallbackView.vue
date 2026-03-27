<template>
  <AuthPageFrame prompt-text="Need an account?" link-text="Sign up" link-to="/register">
    <template #top-nav>
      <LandingTopBar />
    </template>

    <template #left>
      <AuthPromoPanel
        title-line1="Secure"
        title-line2="Google"
        title-line3="authentication"
        description="We are completing your secure sign-in and preparing your workspace with your account permissions."
        footer-lead="Encrypted"
        footer-highlight="OAuth 2.0"
        footer-tail="flow"
      />
    </template>

    <template #right>
      <section class="rounded-3xl border border-white/90 bg-white p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:rounded-4xl sm:p-10 lg:p-16">
        <h2 class="text-3xl font-semibold text-[#1d1f31] dark:text-slate-100 sm:text-4xl">Signing You In</h2>
        <p class="mt-2 text-sm font-medium text-[#7d8195] dark:text-slate-400">Please wait while we finish your Google sign-in.</p>

        <BaseAlert
          v-if="errorMessage"
          type="error"
          class="mt-5"
          :message="errorMessage"
          dismissible
          @dismiss="errorMessage = ''"
        />

        <div class="mt-8 flex items-center gap-3 rounded-xl border border-[#ddd7ea] bg-[#f4f1fb] px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
          <div v-if="loading" class="h-7 w-7 animate-spin rounded-full border-2 border-[#d2caea] border-t-[#4b35cb] dark:border-slate-600 dark:border-t-indigo-300"></div>
          <p class="text-sm font-semibold text-[#2f2952] dark:text-slate-200">
            {{ loading ? 'Verifying token and loading your profile...' : 'Sign-in process paused.' }}
          </p>
        </div>

        <RouterLink
          to="/login"
          class="mt-6 inline-flex text-sm font-semibold text-[#4b35cb] transition hover:text-[#3d28b2] dark:text-indigo-300 dark:hover:text-indigo-200"
        >
          Back to sign in
        </RouterLink>
      </section>
    </template>
  </AuthPageFrame>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthService from '@/services/auth/AuthService'
import { resolveErrorMessage } from '@/services/auth/AuthViewService'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import AuthPageFrame from '@/components/auth/AuthPageFrame.vue'
import AuthPromoPanel from '@/components/auth/AuthPromoPanel.vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'

export default defineComponent({
  name: 'GoogleOAuthCallbackView',
  components: { BaseAlert, AuthPageFrame, AuthPromoPanel, LandingTopBar },
  data() {
    return {
      loading: true,
      errorMessage: '',
    }
  },
  setup() {
    return { auth: useAuthStore() }
  },
  async mounted() {
    const code = this.$route.query.code as string | undefined
    const state = this.$route.query.state as string | undefined

    if (!code) {
      this.loading = false
      this.errorMessage = 'Google callback is missing the authorization code.'
      return
    }

    try {
      const result = await AuthService.completeGoogleOAuth({ code, state })
      this.auth.setAuthenticatedSession(result)
      const redirect = (this.$route.query.redirect as string) || '/dashboard'
      await this.$router.replace(redirect)
    } catch (e: unknown) {
      this.errorMessage = resolveErrorMessage(e, 'Google sign-in failed. Please try again.')
      this.loading = false
    }
  },
})
</script>

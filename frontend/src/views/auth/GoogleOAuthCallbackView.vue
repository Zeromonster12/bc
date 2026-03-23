<template>
  <AuthLayout>
    <div class="space-y-4 text-center">
      <h2 class="text-2xl font-bold text-slate-900">Signing you in</h2>
      <p class="text-sm text-slate-500">Please wait while we finish your Google sign-in.</p>

      <BaseAlert
        v-if="errorMessage"
        type="error"
        class="mb-2"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <div
        v-if="loading"
        class="mx-auto h-9 w-9 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"
      ></div>

      <RouterLink
        to="/login"
        class="inline-block text-sm font-medium text-teal-700 hover:text-teal-800"
      >
        Back to sign in
      </RouterLink>
    </div>
  </AuthLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthService from '@/services/auth/AuthService'
import { resolveErrorMessage } from '@/services/auth/AuthViewService'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

export default defineComponent({
  name: 'GoogleOAuthCallbackView',
  components: { AuthLayout, BaseAlert },
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

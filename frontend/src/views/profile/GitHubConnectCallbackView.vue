<template>
  <AppLayout>
    <div
      class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm"
    >
      <h1 class="text-2xl font-bold text-slate-900">Connecting GitHub</h1>
      <p class="mt-2 text-sm text-slate-500">
        Please wait while we complete the account connection.
      </p>

      <BaseAlert
        v-if="errorMessage"
        type="error"
        class="mt-5"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <div
        v-if="loading"
        class="mx-auto mt-6 h-9 w-9 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"
      ></div>

      <RouterLink
        to="/profile/student"
        class="mt-6 inline-block text-sm font-medium text-teal-700 hover:text-teal-800"
      >
        Back to student profile
      </RouterLink>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import ProfileService from '@/services/profile/ProfileService'

export default defineComponent({
  name: 'GitHubConnectCallbackView',
  components: { AppLayout, BaseAlert },
  data() {
    return {
      loading: true,
      errorMessage: '',
    }
  },
  async mounted() {
    const code = this.$route.query.code as string | undefined
    const state = this.$route.query.state as string | undefined

    if (!code) {
      this.loading = false
      this.errorMessage = 'GitHub callback is missing the authorization code.'
      return
    }

    try {
      await ProfileService.completeGitHubConnection({ code, state })
      await this.$router.replace({
        name: 'profile.student',
        query: { github: 'connected' },
      })
    } catch (error: unknown) {
      const typedError = error as { response?: { data?: { message?: string } } }
      this.errorMessage =
        typedError?.response?.data?.message ??
        'Failed to connect your GitHub account. Please try again.'
      this.loading = false
    }
  },
})
</script>

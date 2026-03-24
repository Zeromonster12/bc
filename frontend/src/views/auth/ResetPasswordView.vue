<template>
  <AuthLayout>
    <h2 class="mb-2 text-center text-2xl font-bold text-gray-900 dark:text-slate-100">Reset password</h2>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-slate-400">
      Enter your new password for <strong>{{ email }}</strong
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

    <form v-if="!successMessage" @submit.prevent="handleSubmit" novalidate>
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
          label="New password"
          type="password"
          autocomplete="new-password"
          :error="errors.password"
          required
        />

        <BaseInput
          v-model="form.password_confirmation"
          label="Confirm new password"
          type="password"
          autocomplete="new-password"
          :error="errors.password_confirmation"
          required
        />
      </div>

      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Save new password
      </BaseButton>
    </form>

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
import { resolveErrorMessage, resolveValidationErrors } from '@/services/auth/AuthViewService'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

export default defineComponent({
  name: 'ResetPasswordView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert },
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

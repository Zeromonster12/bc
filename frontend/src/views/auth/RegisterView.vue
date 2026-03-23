<template>
  <AuthLayout>
    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Create an account</h2>

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

    <form @submit.prevent="handleRegister" novalidate>
      <div class="space-y-4">
        <BaseInput
          v-model="form.name"
          label="Full name"
          type="text"
          autocomplete="name"
          :error="errors.name"
          required
        />
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
          autocomplete="new-password"
          :error="errors.password"
          hint="Min. 8 characters, mixed case and numbers"
          required
        />
        <BaseInput
          v-model="form.password_confirmation"
          label="Confirm password"
          type="password"
          autocomplete="new-password"
          :error="errors.password_confirmation"
          required
        />

        <AuthRoleSelector v-model="form.role" :options="roleOptions" :error="errors.role" />

        <TurnstileWidget ref="turnstileWidget" v-model="turnstileToken" />
      </div>

      <BaseButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-6">
        Create account
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      Already have an account?
      <RouterLink to="/login" class="text-indigo-600 hover:text-indigo-500 font-medium"
        >Sign in</RouterLink
      >
    </p>
  </AuthLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
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
import AuthRoleSelector from '@/components/auth/AuthRoleSelector.vue'

interface ResettableWidgetRef {
  reset?: () => void
}

interface RoleOption {
  value: 'student' | 'company'
  label: string
}

export default defineComponent({
  name: 'RegisterView',
  components: { AuthLayout, BaseInput, BaseButton, BaseAlert, TurnstileWidget, AuthRoleSelector },
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'student' as 'student' | 'company',
      },
      roleOptions: [
        { value: 'student', label: 'Student' },
        { value: 'company', label: 'Company' },
      ] as RoleOption[],
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
    async handleRegister() {
      this.errors = {}
      this.errorMessage = ''
      this.turnstileError = ''

      if (!hasTurnstileToken(this.turnstileToken)) {
        this.turnstileError = 'Please complete the captcha challenge.'
        return
      }

      this.loading = true
      try {
        const result = await this.auth.register({
          ...this.form,
          turnstile_token: this.turnstileToken,
        })
        this.$router.push({
          name: 'verify-email-code',
          query: { email: result.email ?? this.form.email },
        })
      } catch (e: unknown) {
        if (e?.response?.status === 422) {
          this.errors = resolveValidationErrors(e)
        } else {
          this.errorMessage = resolveErrorMessage(e, 'Registration failed. Please try again.')
        }
      } finally {
        ;(this.$refs.turnstileWidget as ResettableWidgetRef | undefined)?.reset?.()
        this.loading = false
      }
    },
  },
})
</script>

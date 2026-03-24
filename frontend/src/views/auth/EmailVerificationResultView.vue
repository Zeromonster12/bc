<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 dark:bg-slate-950">
    <div
      class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"
    >
      <h1 class="mb-2 text-xl font-semibold text-slate-900 dark:text-slate-100">Email verification</h1>
      <p :class="statusClass" class="text-sm mb-6">{{ resolvedMessage }}</p>

      <div class="flex gap-3">
        <RouterLink to="/login" class="flex-1">
          <button
            class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors"
          >
            Go to login
          </button>
        </RouterLink>
        <RouterLink to="/register" class="flex-1">
          <button
            class="w-full px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
          >
            Register
          </button>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { resolveEmailVerificationResultMessage } from '@/services/auth/AuthViewService'

export default defineComponent({
  name: 'EmailVerificationResultView',
  computed: {
    status(): string {
      return String(this.$route.query.status ?? '')
    },
    message(): string {
      return String(this.$route.query.message ?? '')
    },
    statusClass(): string {
      return this.status === 'success'
        ? 'text-emerald-700 dark:text-emerald-300'
        : 'text-rose-700 dark:text-rose-300'
    },
    resolvedMessage(): string {
      return resolveEmailVerificationResultMessage(this.status, this.message)
    },
  },
})
</script>

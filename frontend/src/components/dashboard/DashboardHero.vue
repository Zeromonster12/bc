<template>
  <section class="surface-card overflow-hidden">
    <div class="bg-linear-to-r from-teal-700 via-teal-600 to-cyan-700 px-6 py-8 text-white dark:from-teal-900 dark:via-teal-800 dark:to-cyan-900 sm:px-8">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-100 dark:text-teal-200">Overview</p>
      <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">Welcome back, {{ userName }}</h1>
      <p class="mt-2 text-sm text-teal-50/95 dark:text-teal-100">
        <span v-if="role === 'student'">Browse open projects and track your applications.</span>
        <span v-else-if="role === 'company'"
          >Manage your projects and review incoming applications.</span
        >
        <span v-else>Platform overview and administration.</span>
      </p>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'DashboardHero',
  props: {
    userName: {
      type: String,
      default: 'User',
    },
    role: {
      type: String,
      default: '',
    },
  },
})
</script>

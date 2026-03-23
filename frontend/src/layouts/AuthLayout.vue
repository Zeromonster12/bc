<template>
  <div class="relative min-h-screen overflow-hidden px-4 py-10 sm:px-6">
    <div class="pointer-events-none absolute inset-0">
      <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-teal-300/20 blur-3xl"></div>
      <div class="absolute -right-20 bottom-8 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
    </div>

    <div
      class="relative mx-auto flex min-h-[calc(100vh-5rem)] w-full max-w-5xl items-center justify-center"
    >
      <div class="grid w-full items-stretch gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <section
          class="hidden rounded-3xl border border-white/50 bg-linear-to-br from-teal-700 to-cyan-700 p-10 text-white shadow-xl lg:block"
        >
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-100">BC Platform</p>
          <h1 class="mt-6 text-4xl font-semibold leading-tight">
            Build real-world collaboration between students and teams.
          </h1>
          <p class="mt-4 max-w-md text-sm text-teal-50/90">
            A focused workspace for project matching, application tracking and communication,
            designed for fast decisions.
          </p>
        </section>

        <div class="surface-glass w-full p-5 shadow-xl sm:p-8">
          <div class="mb-8 text-center lg:text-left">
            <h1 class="text-3xl font-extrabold tracking-tight text-teal-700">BC Platform</h1>
            <p class="mt-1 text-sm text-slate-500">Student and company collaboration workspace</p>
          </div>
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AuthLayout',
})
</script>

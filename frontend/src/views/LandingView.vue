<template>
  <div class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -left-20 -top-12 h-72 w-72 rounded-full bg-teal-300/35 blur-3xl"></div>
      <div class="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-cyan-300/35 blur-3xl"></div>
      <div
        class="absolute bottom-0 left-1/3 h-56 w-56 rounded-full bg-emerald-200/45 blur-3xl"
      ></div>
    </div>

    <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 sm:px-10">
      <RouterLink to="/" class="text-lg font-extrabold tracking-tight text-teal-800"
        >ProjectLinker</RouterLink
      >
      <nav class="flex items-center gap-3">
        <RouterLink
          to="/login"
          class="rounded-xl border border-teal-700/30 bg-white/80 px-4 py-2 text-sm font-semibold text-teal-800 transition hover:bg-teal-50"
        >
          Sign in
        </RouterLink>
        <RouterLink
          to="/register"
          class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:bg-teal-800"
        >
          Create account
        </RouterLink>
      </nav>
    </header>

    <main
      class="mx-auto grid w-full max-w-6xl gap-8 px-6 pb-14 pt-6 sm:px-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:pb-24 lg:pt-12"
    >
      <section class="animate-rise">
        <p
          class="inline-flex rounded-full border border-teal-700/20 bg-teal-100/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-teal-900"
        >
          Student x Company Collaboration
        </p>

        <h1
          class="mt-5 text-4xl font-extrabold leading-tight text-slate-900 sm:text-5xl lg:text-6xl"
        >
          Build real projects,
          <span class="text-teal-700">faster and smarter</span>.
        </h1>

        <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
          BC Platform spaja studentov a firmy v jednom priestore. Vyhladavanie projektov, sledovanie
          prihlasok, komunikacia a onboarding bez chaosu v mailoch.
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
          <RouterLink
            to="/register"
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-800/20 transition hover:-translate-y-0.5 hover:bg-black"
          >
            Start now
          </RouterLink>
          <RouterLink
            to="/login"
            class="rounded-xl border border-slate-300 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-white"
          >
            I already have account
          </RouterLink>
        </div>

        <dl class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm backdrop-blur">
            <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
              Projects
            </dt>
            <dd class="mt-1 text-2xl font-extrabold text-slate-900">100+</dd>
          </div>
          <div class="rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm backdrop-blur">
            <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
              Applicants
            </dt>
            <dd class="mt-1 text-2xl font-extrabold text-slate-900">1.8k</dd>
          </div>
          <div class="rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm backdrop-blur">
            <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
              Avg. match time
            </dt>
            <dd class="mt-1 text-2xl font-extrabold text-slate-900">48h</dd>
          </div>
        </dl>
      </section>

      <section
        class="animate-fade-delay rounded-3xl border border-white/70 bg-white/70 p-6 shadow-xl backdrop-blur sm:p-8"
      >
        <h2 class="text-xl font-bold text-slate-900">What you can do in one flow</h2>

        <ul class="mt-5 space-y-4 text-sm text-slate-700 sm:text-base">
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-teal-600"></span>
            Publish or discover project opportunities with role-specific detail.
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-cyan-600"></span>
            Track application status and deadlines in one transparent timeline.
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
            Communicate directly between student and company without tool switching.
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-700"></span>
            Keep records ready for compliance and admin overview.
          </li>
        </ul>

        <div class="mt-7 rounded-2xl border border-teal-200 bg-teal-50/80 p-4 sm:p-5">
          <p class="text-sm font-semibold text-teal-900">Ready to start your next collaboration?</p>
          <p class="mt-1 text-sm text-teal-800/90">
            Create account in less than 2 minutes and launch your first project flow.
          </p>
          <RouterLink
            to="/register"
            class="mt-4 inline-flex rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
          >
            Create free account
          </RouterLink>
        </div>
      </section>
    </main>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'LandingView',
})
</script>

<style scoped>
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeDelay {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-rise {
  animation: rise 0.55s ease-out both;
}

.animate-fade-delay {
  animation: fadeDelay 0.7s ease-out 0.12s both;
}
</style>

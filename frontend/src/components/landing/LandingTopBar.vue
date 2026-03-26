<template>
  <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/80">
    <nav class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3 sm:px-6 sm:py-4">
      <RouterLink to="/" class="text-lg font-extrabold tracking-tight text-indigo-700 sm:text-2xl dark:text-indigo-300">Project Linker</RouterLink>

      <div class="hidden items-center gap-8 md:flex">
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300">Students</a>
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300">Companies</a>
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300">About Us</a>
      </div>

      <div class="flex items-center gap-3 sm:gap-4">
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="toggleTheme"
        >
          <Sun v-if="isDark" class="h-4 w-4" />
          <Moon v-else class="h-4 w-4" />
        </button>
        <RouterLink
          to="/login"
          class="hidden rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 sm:block"
        >
          Log In
        </RouterLink>
        <RouterLink
          to="/register"
          class="rounded-full bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-indigo-200/70 transition hover:brightness-105 dark:shadow-indigo-900/40 sm:px-5 sm:py-2.5 sm:text-sm"
        >
          Get Started
        </RouterLink>
      </div>
    </nav>
  </header>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Moon, Sun } from 'lucide-vue-next'

export default defineComponent({
  name: 'LandingTopBar',
  components: {
    Moon,
    Sun,
  },
  data() {
    return {
      isDark: false,
    }
  },
  mounted() {
    this.initializeTheme()
  },
  methods: {
    initializeTheme() {
      const root = document.documentElement
      const savedTheme = window.localStorage.getItem('theme')

      if (savedTheme === 'dark' || savedTheme === 'light') {
        this.applyTheme(savedTheme === 'dark')
        return
      }

      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      this.applyTheme(root.classList.contains('dark') || prefersDark)
    },
    applyTheme(useDark: boolean) {
      const root = document.documentElement
      root.classList.toggle('dark', useDark)
      window.localStorage.setItem('theme', useDark ? 'dark' : 'light')
      this.isDark = useDark
    },
    toggleTheme() {
      this.applyTheme(!this.isDark)
    },
  },
})
</script>

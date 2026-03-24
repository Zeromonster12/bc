<template>
  <header
    class="w-full border-b border-slate-200/80 bg-white/90 px-2 py-4 backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/90 sm:px-3 sm:py-5 lg:px-4"
  >
    <div class="flex items-center gap-3">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300 lg:hidden"
          @click="$emit('toggle-sidebar')"
          aria-label="Open menu"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
          </svg>
        </button>
        <RouterLink
          to="/"
          class="text-lg font-semibold text-slate-900 transition hover:text-[#4e3aba] dark:text-slate-100 dark:hover:text-slate-200"
        >
          ProjectLinker
        </RouterLink>
      </div>
    </div>
  </header>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TopNav',
  emits: ['toggle-sidebar'],
})
</script>

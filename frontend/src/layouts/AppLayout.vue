<template>
  <div class="min-h-screen bg-transparent">
    <button
      type="button"
      class="fixed left-4 top-4 z-50 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-600 shadow-sm backdrop-blur lg:hidden dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-300"
      @click="sidebarOpen = !sidebarOpen"
      aria-label="Open menu"
    >
      <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
      </svg>
    </button>

    <div class="flex min-h-screen">
      <aside class="hidden self-start p-4 lg:sticky lg:top-0 lg:block lg:h-screen">
        <Sidebar :collapsed="true" />
      </aside>

      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="sidebarOpen"
          class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden dark:bg-slate-950/60"
          @click="sidebarOpen = false"
        />
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-200"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-150"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
      >
        <aside v-if="sidebarOpen" class="fixed left-0 top-0 z-50 h-full lg:hidden">
          <Sidebar mobile @navigate="sidebarOpen = false" />
        </aside>
      </Transition>

      <div class="flex-1 flex flex-col">
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'

export default defineComponent({
  name: 'AppLayout',
  components: { Sidebar },
  data() {
    return {
      sidebarOpen: false,
    }
  },
})
</script>

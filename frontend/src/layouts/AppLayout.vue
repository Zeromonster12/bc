<template>
  <div class="flex min-h-screen bg-transparent overflow-hidden">
    <aside class="hidden lg:block">
      <Sidebar />
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
        class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
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

    <div class="flex-1 flex flex-col overflow-hidden">
      <TopNav @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'

export default defineComponent({
  name: 'AppLayout',
  components: { Sidebar, TopNav },
  data() {
    return {
      sidebarOpen: false,
    }
  },
})
</script>

<template>
  <div class="rounded-3xl border border-slate-200/90 bg-white/95 p-2 shadow-[0_12px_30px_rgba(15,23,42,0.08)] dark:border-slate-700/70 dark:bg-slate-900/90">
    <nav class="flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('change', tab.id)"
        :class="[
          'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition',
          activeTab === tab.id
            ? 'bg-linear-to-r from-[#4526c9] to-[#5b45f0] text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)]'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100',
        ]"
      >
        {{ tab.label }}
      </button>
    </nav>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface AdminTab {
  id: string
  label: string
}

export default defineComponent({
  name: 'AdminTabNav',
  props: {
    tabs: {
      type: Array as PropType<AdminTab[]>,
      required: true,
    },
    activeTab: {
      type: String,
      required: true,
    },
  },
  emits: ['change'],
})
</script>

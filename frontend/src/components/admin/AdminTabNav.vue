<template>
  <div class="rounded-3xl bg-white p-2 dark:bg-slate-900/90">
    <nav class="flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('change', tab.id)"
        :class="[
          'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition',
          activeTab === tab.id
            ? 'bg-[#4b35cb] text-white'
            : 'bg-[#f1edf8] text-[#5a5476] hover:bg-[#e8e3f2] dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100',
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

<template>
  <div v-if="lastPage > 1" class="flex items-center justify-between">
    <p class="text-sm text-gray-600 dark:text-slate-300">
      Page {{ currentPage }} of {{ lastPage }} &mdash; {{ total }} total
    </p>
    <div class="flex items-center gap-1">
      <button
        :disabled="currentPage <= 1"
        class="rounded border p-1.5 text-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        @click="$emit('change', currentPage - 1)"
      >
        &larr; Prev
      </button>
      <button
        v-for="page in visiblePages"
        :key="page"
        :class="[
          'px-3 py-1.5 rounded border text-sm transition',
          page === currentPage
            ? 'border-indigo-600 bg-indigo-600 text-white dark:border-indigo-500 dark:bg-indigo-500'
            : 'dark:border-slate-600 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-800',
        ]"
        @click="$emit('change', page)"
      >
        {{ page }}
      </button>
      <button
        :disabled="currentPage >= lastPage"
        class="rounded border p-1.5 text-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        @click="$emit('change', currentPage + 1)"
      >
        Next &rarr;
      </button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BasePagination',
  props: {
    currentPage: {
      type: Number,
      required: true,
    },
    lastPage: {
      type: Number,
      required: true,
    },
    total: {
      type: Number,
      default: 0,
    },
  },
  emits: ['change'],
  computed: {
    visiblePages(): number[] {
      const range: number[] = []
      const delta = 2
      const start = Math.max(1, this.currentPage - delta)
      const end = Math.min(this.lastPage, this.currentPage + delta)
      for (let i = start; i <= end; i++) range.push(i)
      return range
    },
  },
})
</script>

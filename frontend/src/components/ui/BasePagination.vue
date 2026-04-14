<template>
  <div v-if="lastPage > 1" :class="isCompanyApplicationsVariant ? 'grid gap-4 lg:grid-cols-[1fr_auto]' : 'flex items-center justify-between'">
    <template v-if="isCompanyApplicationsVariant">
      <div class="rounded-2xl bg-white px-5 py-4 text-sm text-[#6c6786] dark:bg-slate-900/90 dark:text-slate-300">
        Page {{ currentPage }} of {{ lastPage }} · {{ total }} total
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 transition hover:bg-[#e9e2fb] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900/90 dark:text-slate-300"
          @click="$emit('change', currentPage - 1)"
        >
          <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
          </svg>
        </button>
        <span class="inline-flex min-w-18 items-center justify-center rounded-xl bg-[#4f33d7] px-4 py-2 text-sm font-bold text-white">
          {{ currentPage }} / {{ lastPage }}
        </span>
        <button
          type="button"
          :disabled="currentPage >= lastPage"
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 transition hover:bg-[#e9e2fb] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900/90 dark:text-slate-300"
          @click="$emit('change', currentPage + 1)"
        >
          <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
          </svg>
        </button>
      </div>
    </template>

    <template v-else>
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
    </template>
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
    variant: {
      type: String as () => 'default' | 'company-applications',
      default: 'default',
    },
  },
  emits: ['change'],
  computed: {
    isCompanyApplicationsVariant(): boolean {
      return this.variant === 'company-applications'
    },
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

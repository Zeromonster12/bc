<template>
  <div
    class="relative z-30"
    :class="
      isSidebar
        ? 'rounded-3xl border border-[#ddd7ea] bg-[#efedf5] p-6 shadow-[0_10px_24px_rgba(77,55,197,0.08)] dark:border-slate-700 dark:bg-slate-900/95'
        : 'rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-[0_6px_18px_rgba(15,23,42,0.05)] backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/90 dark:shadow-[0_8px_20px_rgba(2,6,23,0.3)] sm:p-4'
    "
  >
    <div
      class="mb-3 flex flex-wrap items-center justify-between gap-2"
      :class="isSidebar ? 'pb-1' : 'border-b border-slate-200/70 pb-2 dark:border-slate-700/70'"
    >
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.12em]"
          :class="isSidebar ? 'text-[#77718f] dark:text-slate-400' : 'text-slate-500 dark:text-slate-400'"
        >
          {{ isSidebar ? 'Project filters' : 'Project Discovery' }}
        </p>
        <h3 class="text-xs font-semibold" :class="isSidebar ? 'text-[#343047] dark:text-slate-100' : 'text-slate-900 dark:text-slate-100'">
          {{ isSidebar ? 'Refine opportunities' : 'Filter opportunities' }}
        </h3>
      </div>
      <div v-if="!isSidebar" class="flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>

    <div class="flex flex-wrap items-end gap-3" :class="isSidebar ? 'space-y-1' : ''">
      <div :class="isSidebar ? 'w-full' : 'w-full sm:w-75'">
        <label class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Search</label>
        <div class="relative">
          <input
            v-model="localFilters.search"
            type="text"
            placeholder="Search by title or description"
            class="w-full border border-slate-300 bg-white px-3 py-2 pl-9 text-xs text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-indigo-400"
            :class="isSidebar ? 'rounded-full' : 'rounded-lg'"
            @input="debouncedEmit"
          />
          <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" />
          </svg>
        </div>
      </div>

      <div :class="isSidebar ? 'w-full' : 'w-full sm:w-60'">
        <label class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Technologies</label>
        <div ref="techDropdown" class="relative">
          <button
            type="button"
            class="inline-flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2 text-left text-xs text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            :class="isSidebar ? 'rounded-full' : 'rounded-lg'"
            :aria-expanded="techMenuOpen"
            aria-haspopup="listbox"
            @click="toggleTechMenu"
          >
            <span>{{ selectedTechLabel }}</span>
            <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-500 transition dark:text-slate-400" :class="techMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
          </button>

          <div
            v-if="techMenuOpen"
            class="absolute z-80 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-[0_10px_30px_rgba(15,23,42,0.16)] dark:border-slate-700 dark:bg-slate-900"
            role="listbox"
            aria-multiselectable="true"
          >
            <button
              v-if="localFilters.tech_stack.length"
              type="button"
              class="mb-1 flex w-full items-center justify-center rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="clearTechSelection"
            >
              Clear selection
            </button>
            <div v-if="normalizedTechOptions.length" class="project-filter-scrollbar max-h-44 overflow-y-auto">
              <button
                v-for="option in normalizedTechOptions"
                :key="option"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-xs transition"
                :class="
                  localFilters.tech_stack.includes(option)
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                    : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                "
                role="option"
                :aria-selected="localFilters.tech_stack.includes(option)"
                @click="toggleTechOption(option)"
              >
                <span>{{ option }}</span>
                <svg
                  v-if="localFilters.tech_stack.includes(option)"
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
            <div v-else class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">No technologies available</div>
          </div>
        </div>
      </div>

      <div :class="isSidebar ? 'w-full' : 'w-full sm:w-47.5'">
        <label class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Location</label>
        <div class="relative">
          <input
            v-model="localFilters.location"
            type="text"
            placeholder="City / region"
            class="w-full border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-indigo-400"
            :class="isSidebar ? 'rounded-full' : 'rounded-lg'"
            @input="debouncedEmit"
          />
        </div>
      </div>

      <div :class="isSidebar ? 'w-full' : 'w-full sm:w-45'">
        <label class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Status</label>
        <div ref="statusDropdown" class="relative w-full">
            <button
              type="button"
              class="inline-flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2 text-left text-xs text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :class="isSidebar ? 'rounded-full' : 'rounded-lg'"
              :aria-expanded="statusMenuOpen"
              aria-haspopup="listbox"
              @click="toggleStatusMenu"
            >
              <span>{{ selectedStatusLabel }}</span>
              <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-500 transition dark:text-slate-400" :class="statusMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
              </svg>
            </button>

            <div
              v-if="statusMenuOpen"
              class="absolute z-80 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-[0_10px_30px_rgba(15,23,42,0.16)] dark:border-slate-700 dark:bg-slate-900"
              role="listbox"
            >
              <button
                v-for="option in statusOptions"
                :key="option.value"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-xs transition"
                :class="
                  localFilters.status === option.value
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                    : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                "
                role="option"
                :aria-selected="localFilters.status === option.value"
                @click="selectStatus(option.value)"
              >
                <span>{{ option.label }}</span>
                <svg
                  v-if="localFilters.status === option.value"
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
        </div>
      </div>

      <div :class="isSidebar ? 'w-full' : 'w-full sm:w-42.5'">
        <label class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Sort by date</label>
        <div ref="sortDropdown" class="relative w-full">
          <button
            type="button"
            class="inline-flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2 text-left text-xs text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            :class="isSidebar ? 'rounded-full' : 'rounded-lg'"
            :aria-expanded="sortMenuOpen"
            aria-haspopup="listbox"
            @click="toggleSortMenu"
          >
            <span>{{ selectedSortLabel }}</span>
            <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-500 transition dark:text-slate-400" :class="sortMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
          </button>

          <div
            v-if="sortMenuOpen"
            class="absolute z-80 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-[0_10px_30px_rgba(15,23,42,0.16)] dark:border-slate-700 dark:bg-slate-900"
            role="listbox"
          >
            <button
              v-for="option in sortOptions"
              :key="option.value"
              type="button"
              class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-xs transition"
              :class="
                localFilters.sort_date === option.value
                  ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                  : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
              "
              role="option"
              :aria-selected="localFilters.sort_date === option.value"
              @click="selectSort(option.value)"
            >
              <span>{{ option.label }}</span>
              <svg
                v-if="localFilters.sort_date === option.value"
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="w-full" :class="isSidebar ? 'mt-1 space-y-2' : 'sm:w-auto'">
        <button
          class="inline-flex w-full items-center justify-center border border-[#ebeaf2] bg-white px-3 py-2 text-xs font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
          :class="isSidebar ? 'rounded-full' : 'rounded-lg sm:w-auto'"
          @click="resetFilters"
        >
          Reset filters
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ProjectFilters',
  props: {
    techOptions: {
      type: Array as () => string[],
      default: () => [],
    },
    variant: {
      type: String as () => 'inline' | 'sidebar',
      default: 'inline',
    },
  },
  emits: ['filter', 'change'],
  data() {
    return {
      localFilters: {
        search: '',
        status: '',
        location: '',
        sort_date: 'newest' as 'newest' | 'oldest',
        tech_stack: [] as string[],
      },
      debounceTimer: null as ReturnType<typeof setTimeout> | null,
      statusMenuOpen: false,
      techMenuOpen: false,
      sortMenuOpen: false,
      statusOptions: [
        { value: '', label: 'All (Open + Closed)' },
        { value: 'open', label: 'Open' },
        { value: 'closed', label: 'Closed' },
      ] as Array<{ value: string; label: string }>,
      sortOptions: [
        { value: 'newest', label: 'Newest first' },
        { value: 'oldest', label: 'Oldest first' },
      ] as Array<{ value: 'newest' | 'oldest'; label: string }>,
    }
  },
  computed: {
    isSidebar(): boolean {
      return this.variant === 'sidebar'
    },
    selectedStatusLabel(): string {
      const selected = this.statusOptions.find((option) => option.value === this.localFilters.status)
      return selected?.label ?? 'All statuses'
    },
    selectedTechLabel(): string {
      if (!this.localFilters.tech_stack.length) return 'All technologies'
      if (this.localFilters.tech_stack.length === 1) return this.localFilters.tech_stack[0]
      return `${this.localFilters.tech_stack.length} selected`
    },
    selectedSortLabel(): string {
      const selected = this.sortOptions.find((option) => option.value === this.localFilters.sort_date)
      return selected?.label ?? 'Newest first'
    },
    normalizedTechOptions(): string[] {
      const merged = [...this.techOptions, ...this.localFilters.tech_stack]
      return Array.from(new Set(merged.filter((item) => item && item.trim().length > 0))).sort((a, b) =>
        a.localeCompare(b),
      )
    },
  },
  mounted() {
    document.addEventListener('click', this.handleOutsideClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
  },
  methods: {
    toggleTechMenu() {
      this.techMenuOpen = !this.techMenuOpen
    },
    toggleTechOption(value: string) {
      if (this.localFilters.tech_stack.includes(value)) {
        this.localFilters.tech_stack = this.localFilters.tech_stack.filter((item) => item !== value)
      } else {
        this.localFilters.tech_stack = [...this.localFilters.tech_stack, value]
      }
      this.emitFilters()
    },
    clearTechSelection() {
      this.localFilters.tech_stack = []
      this.emitFilters()
    },
    toggleStatusMenu() {
      this.statusMenuOpen = !this.statusMenuOpen
    },
    toggleSortMenu() {
      this.sortMenuOpen = !this.sortMenuOpen
    },
    selectStatus(value: string) {
      this.localFilters.status = value
      this.statusMenuOpen = false
      this.emitFilters()
    },
    selectSort(value: 'newest' | 'oldest') {
      this.localFilters.sort_date = value
      this.sortMenuOpen = false
      this.emitFilters()
    },
    handleOutsideClick(event: MouseEvent) {
      const statusContainer = this.$refs.statusDropdown as HTMLElement | undefined
      const techContainer = this.$refs.techDropdown as HTMLElement | undefined
      const sortContainer = this.$refs.sortDropdown as HTMLElement | undefined
      if (statusContainer && !statusContainer.contains(event.target as Node)) {
        this.statusMenuOpen = false
      }
      if (techContainer && !techContainer.contains(event.target as Node)) {
        this.techMenuOpen = false
      }
      if (sortContainer && !sortContainer.contains(event.target as Node)) {
        this.sortMenuOpen = false
      }
    },
    debouncedEmit() {
      if (this.debounceTimer) clearTimeout(this.debounceTimer)
      this.debounceTimer = setTimeout(() => this.emitFilters(), 400)
    },
    emitFilters() {
      const filters: Record<string, string | string[]> = {}
      if (this.localFilters.search) filters.search = this.localFilters.search
      if (this.localFilters.status) filters.status = this.localFilters.status
      if (this.localFilters.location.trim()) filters.location = this.localFilters.location.trim()
      if (this.localFilters.sort_date !== 'newest') filters.sort_date = this.localFilters.sort_date
      if (this.localFilters.tech_stack.length) filters.tech_stack = this.localFilters.tech_stack
      this.$emit('filter', filters)
      this.$emit('change', filters)
    },
    resetFilters() {
      this.localFilters = { search: '', status: '', location: '', sort_date: 'newest', tech_stack: [] }
      this.statusMenuOpen = false
      this.techMenuOpen = false
      this.sortMenuOpen = false
      this.$emit('filter', {})
      this.$emit('change', {})
    },
  },
})
</script>

<style scoped>
.project-filter-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(99, 102, 241, 0.65) rgba(148, 163, 184, 0.22);
}

.project-filter-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.project-filter-scrollbar::-webkit-scrollbar-track {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 9999px;
}

.project-filter-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(99, 102, 241, 0.85), rgba(79, 70, 229, 0.9));
  border-radius: 9999px;
}

.project-filter-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, rgba(79, 70, 229, 0.95), rgba(67, 56, 202, 0.95));
}

:global(.dark) .project-filter-scrollbar {
  scrollbar-color: rgba(129, 140, 248, 0.75) rgba(51, 65, 85, 0.45);
}

:global(.dark) .project-filter-scrollbar::-webkit-scrollbar-track {
  background: rgba(51, 65, 85, 0.45);
}

:global(.dark) .project-filter-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(129, 140, 248, 0.85), rgba(99, 102, 241, 0.9));
}
</style>

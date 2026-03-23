<template>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input
          v-model="localFilters.search"
          type="text"
          placeholder="Title or description..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @input="debouncedEmit"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select
          v-model="localFilters.status"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="emitFilters"
        >
          <option value="">All statuses</option>
          <option value="open">Open</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          class="w-full px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          @click="resetFilters"
        >
          Reset Filters
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ProjectFilters',
  emits: ['filter'],
  data() {
    return {
      localFilters: {
        search: '',
        status: '',
      },
      debounceTimer: null as ReturnType<typeof setTimeout> | null,
    }
  },
  methods: {
    debouncedEmit() {
      if (this.debounceTimer) clearTimeout(this.debounceTimer)
      this.debounceTimer = setTimeout(() => this.emitFilters(), 400)
    },
    emitFilters() {
      const filters: Record<string, string> = {}
      if (this.localFilters.search) filters.search = this.localFilters.search
      if (this.localFilters.status) filters.status = this.localFilters.status
      this.$emit('filter', filters)
    },
    resetFilters() {
      this.localFilters = { search: '', status: '' }
      this.$emit('filter', {})
    },
  },
})
</script>

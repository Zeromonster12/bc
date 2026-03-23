<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      statusClasses,
    ]"
  >
    {{ label }}
  </span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

const CONFIG: Record<string, { label: string; classes: string }> = {
  draft: { label: 'Draft', classes: 'bg-gray-100 text-gray-600' },
  open: { label: 'Open', classes: 'bg-green-100 text-green-700' },
  in_progress: { label: 'In Progress', classes: 'bg-blue-100 text-blue-700' },
  completed: { label: 'Completed', classes: 'bg-indigo-100 text-indigo-700' },
  cancelled: { label: 'Cancelled', classes: 'bg-red-100 text-red-600' },
}

const FALLBACK_CONFIG = { label: 'Draft', classes: 'bg-gray-100 text-gray-600' }

export default defineComponent({
  name: 'ProjectStatusBadge',
  props: {
    status: {
      type: String,
      required: true,
    },
  },
  computed: {
    config(): { label: string; classes: string } {
      const config = CONFIG[this.status]
      return config ?? FALLBACK_CONFIG
    },
    label(): string {
      return this.config.label
    },
    statusClasses(): string {
      return this.config.classes
    },
  },
})
</script>

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
  pending: { label: 'Pending', classes: 'bg-yellow-100 text-yellow-700' },
  accepted: { label: 'Accepted', classes: 'bg-green-100 text-green-700' },
  rejected: { label: 'Rejected', classes: 'bg-red-100 text-red-600' },
  withdrawn: { label: 'Withdrawn', classes: 'bg-gray-100 text-gray-600' },
}

const FALLBACK_CONFIG = { label: 'Pending', classes: 'bg-yellow-100 text-yellow-700' }

export default defineComponent({
  name: 'ApplicationStatusBadge',
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

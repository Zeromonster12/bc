<template>
  <div class="border-b border-gray-200">
    <nav class="flex gap-6">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('change', tab.id)"
        :class="[
          'pb-3 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab.id
            ? 'border-indigo-600 text-indigo-600'
            : 'border-transparent text-gray-500 hover:text-gray-700',
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

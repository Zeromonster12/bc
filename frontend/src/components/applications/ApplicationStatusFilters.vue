<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      @click="selectStatus(option.value)"
      :class="[
        'px-4 py-1.5 text-sm rounded-full border transition-colors',
        activeStatus === option.value
          ? 'bg-indigo-600 text-white border-indigo-600'
          : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300',
      ]"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface StatusOption {
  value: string
  label: string
}

export default defineComponent({
  name: 'ApplicationStatusFilters',
  props: {
    activeStatus: {
      type: String,
      required: true,
    },
    options: {
      type: Array as PropType<StatusOption[]>,
      required: true,
    },
  },
  emits: ['change'],
  methods: {
    selectStatus(status: string) {
      this.$emit('change', status)
    },
  },
})
</script>

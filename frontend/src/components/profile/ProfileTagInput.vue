<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
    <div class="flex flex-wrap gap-2 mb-2">
      <span v-for="(tag, index) in tags" :key="`${label}-${index}`" :class="chipClass">
        {{ tag }}
        <button type="button" @click="$emit('remove', index)" :class="removeClass">&times;</button>
      </span>
    </div>
    <div class="flex gap-2">
      <input
        :value="inputValue"
        type="text"
        class="flex-1 border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        :placeholder="placeholder"
        @input="$emit('update:inputValue', ($event.target as HTMLInputElement).value)"
        @keydown.enter.prevent="$emit('add')"
        @keydown="handleInputKeydown"
      />
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 px-3 py-1.5 text-sm bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500"
        @click="$emit('add')"
      >
        Add
      </button>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

export default defineComponent({
  name: 'ProfileTagInput',
  props: {
    label: {
      type: String,
      required: true,
    },
    tags: {
      type: Array as PropType<string[]>,
      required: true,
    },
    inputValue: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
    tone: {
      type: String,
      default: 'indigo',
    },
    error: {
      type: String,
      default: '',
    },
  },
  emits: ['update:inputValue', 'add', 'remove'],
  computed: {
    chipClass(): string {
      const tones: Record<string, string> = {
        indigo:
          'inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-sm rounded-lg',
        emerald:
          'inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 text-sm rounded-lg',
        amber:
          'inline-flex items-center gap-1 px-3 py-1 bg-amber-50 text-amber-700 text-sm rounded-lg',
      }
      const fallback = tones.indigo ?? ''
      return tones[this.tone] ?? fallback
    },
    removeClass(): string {
      const tones: Record<string, string> = {
        indigo: 'text-indigo-400 hover:text-indigo-700 leading-none',
        emerald: 'text-emerald-400 hover:text-emerald-700 leading-none',
        amber: 'text-amber-400 hover:text-amber-700 leading-none',
      }
      const fallback = tones.indigo ?? ''
      return tones[this.tone] ?? fallback
    },
  },
  methods: {
    handleInputKeydown(event: KeyboardEvent) {
      if (event.key === ',') {
        event.preventDefault()
        this.$emit('add')
      }
    },
  },
})
</script>

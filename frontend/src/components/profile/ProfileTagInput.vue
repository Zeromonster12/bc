<template>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ label }}</label>
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
        class="flex-1 rounded-2xl bg-[#f1edf8] px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
        :placeholder="placeholder"
        @input="$emit('update:inputValue', ($event.target as HTMLInputElement).value)"
        @keydown.enter.prevent="$emit('add')"
        @keydown="handleInputKeydown"
      />
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-full bg-[#3f34a6] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#352b91] focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:bg-indigo-600 dark:hover:bg-indigo-500"
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
          'inline-flex items-center gap-1 rounded-full bg-[#e8e3f2] px-3 py-1 text-sm text-[#4d466b] dark:bg-indigo-500/20 dark:text-indigo-200',
        emerald:
          'inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        amber:
          'inline-flex items-center gap-1 rounded-full bg-[#fdf1dc] px-3 py-1 text-sm text-[#9b5b00] dark:bg-amber-500/20 dark:text-amber-300',
      }
      const fallback = tones.indigo ?? ''
      return tones[this.tone] ?? fallback
    },
    removeClass(): string {
      const tones: Record<string, string> = {
        indigo: 'leading-none text-[#6d5bb8] hover:text-[#3f34a6] dark:text-indigo-300 dark:hover:text-indigo-200',
        emerald: 'leading-none text-emerald-400 hover:text-emerald-700 dark:text-emerald-300 dark:hover:text-emerald-200',
        amber: 'leading-none text-amber-400 hover:text-amber-700 dark:text-amber-300 dark:hover:text-amber-200',
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

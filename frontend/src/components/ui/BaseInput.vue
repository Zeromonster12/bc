<template>
  <div>
    <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :class="[
        'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition',
        'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:focus:ring-indigo-400',
        hasError
          ? 'border-red-400 bg-red-50 text-red-900 placeholder-red-300 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300 dark:placeholder-red-500'
          : 'border-gray-300 bg-white text-slate-900 placeholder-gray-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500',
        disabled ? 'cursor-not-allowed bg-gray-50 opacity-50 dark:bg-slate-800' : '',
      ]"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @blur="$emit('blur', $event)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ hint }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseInput',
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    id: {
      type: String,
      default: () => `input-${Math.random().toString(36).slice(2)}`,
    },
    label: String,
    type: {
      type: String,
      default: 'text',
    },
    placeholder: String,
    hint: String,
    error: String,
    required: Boolean,
    disabled: Boolean,
    autocomplete: String,
  },
  emits: ['update:modelValue', 'blur'],
  computed: {
    hasError(): boolean {
      return !!this.error
    },
  },
})
</script>

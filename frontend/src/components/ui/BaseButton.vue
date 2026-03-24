<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2',
      sizeClasses,
      variantClasses,
      disabled || loading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
    ]"
    @click="$emit('click', $event)"
  >
    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    <slot />
  </button>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseButton',
  props: {
    variant: {
      type: String,
      default: 'primary',
    },
    size: {
      type: String,
      default: 'md',
    },
    type: {
      type: String as () => 'button' | 'submit' | 'reset',
      default: 'button',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['click'],
  computed: {
    variantClasses(): string {
      const variants: Record<string, string> = {
        primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-indigo-400',
        secondary:
          'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-indigo-400',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-400 dark:focus:ring-red-400',
        ghost: 'text-indigo-600 hover:bg-indigo-50 focus:ring-indigo-500 dark:text-indigo-300 dark:hover:bg-indigo-500/10 dark:focus:ring-indigo-400',
      }
      const fallback = 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-indigo-400'
      return variants[this.variant] ?? fallback
    },
    sizeClasses(): string {
      const sizes: Record<string, string> = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
      }
      const fallback = 'px-4 py-2 text-sm'
      return sizes[this.size] ?? fallback
    },
  },
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
      >
        <div class="absolute inset-0 bg-black/50" @click="$emit('update:modelValue', false)" />
        <div :class="['relative bg-white rounded-xl shadow-xl w-full', maxWidthClass]" @click.stop>
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
            <button
              class="text-gray-400 hover:text-gray-600 transition"
              @click="$emit('update:modelValue', false)"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
          <div class="px-6 py-4">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-6 py-4 border-t flex justify-end gap-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseModal',
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    title: {
      type: String,
      default: 'Modal',
    },
    maxWidth: {
      type: String,
      default: 'lg',
    },
  },
  emits: ['update:modelValue'],
  computed: {
    maxWidthClass(): string {
      const widths: Record<string, string> = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
      }
      const fallback = 'max-w-lg'
      return widths[this.maxWidth] ?? fallback
    },
  },
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>

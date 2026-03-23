<template>
  <Transition name="alert">
    <div
      v-if="show"
      :class="['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', variantClasses]"
      role="alert"
    >
      <div class="flex-1">
        <p v-if="title" class="font-semibold">{{ title }}</p>
        <p>{{ message }}</p>
      </div>
      <button
        v-if="dismissible"
        class="flex-shrink-0 opacity-60 hover:opacity-100 transition"
        @click="dismiss"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </Transition>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseAlert',
  props: {
    type: {
      type: String,
      default: 'info',
    },
    title: String,
    message: {
      type: String,
      required: true,
    },
    dismissible: {
      type: Boolean,
      default: true,
    },
  },
  emits: ['dismiss'],
  data() {
    return {
      show: true,
    }
  },
  computed: {
    variantClasses(): string {
      const variants: Record<string, string> = {
        info: 'bg-blue-50 border-blue-200 text-blue-800',
        success: 'bg-green-50 border-green-200 text-green-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        error: 'bg-red-50 border-red-200 text-red-800',
      }
      const fallback = 'bg-blue-50 border-blue-200 text-blue-800'
      return variants[this.type] ?? fallback
    },
  },
  methods: {
    dismiss() {
      this.show = false
      this.$emit('dismiss')
    },
  },
})
</script>

<style scoped>
.alert-enter-active,
.alert-leave-active {
  transition: all 0.2s ease;
}
.alert-enter-from,
.alert-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

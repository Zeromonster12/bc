<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">I am a...</label>
    <div class="grid grid-cols-2 gap-3">
      <button
        v-for="option in options"
        :key="option.value"
        type="button"
        @click="$emit('update:modelValue', option.value)"
        :class="[
          'flex items-center justify-center p-3 border rounded-xl text-sm font-semibold transition-colors',
          modelValue === option.value
            ? 'border-teal-600 bg-teal-50 text-teal-700'
            : 'border-slate-200 text-slate-600 hover:border-slate-300',
        ]"
      >
        {{ option.label }}
      </button>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface RoleOption {
  value: 'student' | 'company'
  label: string
}

export default defineComponent({
  name: 'AuthRoleSelector',
  props: {
    modelValue: {
      type: String as PropType<'student' | 'company'>,
      required: true,
    },
    options: {
      type: Array as PropType<RoleOption[]>,
      required: true,
    },
    error: {
      type: String,
      default: '',
    },
  },
  emits: ['update:modelValue'],
})
</script>

<template>
  <div :class="['rounded-2xl border p-5 shadow-sm', toneClass]">
    <div class="h-1.5 w-10 rounded-full bg-current opacity-30"></div>
    <p class="mt-4 text-3xl font-bold text-slate-900">{{ value ?? '-' }}</p>
    <p class="mt-1 text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ label }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'StatCard',
  props: {
    label: {
      type: String,
      default: '',
    },
    value: {
      type: [Number, String],
      default: '-',
    },
    tone: {
      type: String,
      default: 'slate',
    },
  },
  computed: {
    toneClass(): string {
      const tones: Record<string, string> = {
        teal: 'bg-teal-50 border-teal-100 text-teal-800',
        cyan: 'bg-cyan-50 border-cyan-100 text-cyan-800',
        slate: 'bg-slate-50 border-slate-200 text-slate-700',
        emerald: 'bg-emerald-50 border-emerald-100 text-emerald-800',
      }

      const fallback = tones.slate ?? ''
      return tones[this.tone] ?? fallback
    },
  },
})
</script>

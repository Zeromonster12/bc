<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
      statusClasses,
    ]"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClasses"></span>
    {{ label }}
  </span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

const CONFIG: Record<string, { label: string; classes: string; dotClasses: string }> = {
  draft: {
    label: 'Draft',
    classes: 'bg-[#e8e3f2] text-[#4d466b] ring-[#ddd7f6] dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600',
    dotClasses: 'bg-[#5f6078] dark:bg-slate-300',
  },
  open: {
    label: 'Open',
    classes: 'bg-green-100 text-green-700 ring-green-200 dark:bg-green-500/20 dark:text-green-300 dark:ring-green-500/40',
    dotClasses: 'bg-green-500 dark:bg-green-300',
  },
  closed: {
    label: 'Closed',
    classes: 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600',
    dotClasses: 'bg-slate-500 dark:bg-slate-300',
  },
}

const FALLBACK_CONFIG = {
  label: 'Draft',
  classes: 'bg-[#e8e3f2] text-[#4d466b] ring-[#ddd7f6] dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600',
  dotClasses: 'bg-[#5f6078] dark:bg-slate-300',
}

export default defineComponent({
  name: 'ProjectStatusBadge',
  props: {
    status: {
      type: String,
      required: true,
    },
  },
  computed: {
    config(): { label: string; classes: string; dotClasses: string } {
      const config = CONFIG[this.status]
      return config ?? FALLBACK_CONFIG
    },
    label(): string {
      return this.config.label
    },
    statusClasses(): string {
      return this.config.classes
    },
    statusDotClasses(): string {
      return this.config.dotClasses
    },
  },
})
</script>

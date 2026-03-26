<template>
  <header class="mx-auto flex w-full max-w-6xl flex-col items-start gap-2 py-3 sm:flex-row sm:items-center sm:justify-between sm:py-5">
    <RouterLink to="/" class="text-xl font-semibold text-[#20244f] sm:text-2xl">Project Linker</RouterLink>
    <p class="text-xs font-medium text-[#2f3490] sm:text-sm">
      {{ promptText }}
      <RouterLink :to="linkTo" class="font-semibold hover:text-[#222772]">{{ linkText }}</RouterLink>
    </p>
  </header>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

export default defineComponent({
  name: 'AuthTopNav',
  props: {
    promptText: {
      type: String,
      required: true,
    },
    linkText: {
      type: String,
      required: true,
    },
    linkTo: {
      type: [String, Object] as PropType<string | Record<string, unknown>>,
      required: true,
    },
  },
})
</script>

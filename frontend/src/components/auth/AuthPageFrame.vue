<template>
  <div class="min-h-screen bg-[#f8f4ff] px-3 py-3 sm:px-6 sm:py-0 lg:px-8">
    <slot name="top-nav">
      <AuthTopNav :prompt-text="promptText" :link-text="linkText" :link-to="linkTo" />
    </slot>

    <main
      :class="[
        'mx-auto flex w-full max-w-6xl items-center justify-center py-2 sm:py-4 lg:min-h-[calc(100vh-88px)]',
        hasCustomTopNav ? 'pt-20 sm:pt-24' : '',
      ]"
    >
      <div class="grid w-full gap-4 sm:gap-6 lg:gap-8 lg:grid-cols-[1.05fr_1fr]">
        <slot name="left" />
        <slot name="right" />
      </div>
    </main>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import AuthTopNav from '@/components/auth/AuthTopNav.vue'

export default defineComponent({
  name: 'AuthPageFrame',
  components: {
    AuthTopNav,
  },
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
  computed: {
    hasCustomTopNav(): boolean {
      return Boolean(this.$slots['top-nav'])
    },
  },
})
</script>

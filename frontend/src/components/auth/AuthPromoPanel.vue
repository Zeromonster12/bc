<template>
  <section
    class="relative hidden overflow-hidden rounded-4xl bg-[#f6f1ff] px-10 py-12 shadow-[0_10px_30px_rgba(62,43,131,0.08)] lg:flex lg:flex-col lg:justify-between"
  >
    <div>
      <span class="inline-flex rounded-full bg-[#dcd5f2] px-4 py-1 text-xs font-semibold tracking-[0.18em] text-[#4d37c5]">
        {{ badgeText }}
      </span>
      <h1 class="mt-7 text-6xl font-semibold leading-[1.03] text-[#15162e]">
        {{ titleLine1 }}<br />
        {{ titleLine2 }}<br />
        {{ titleLine3 }}
        <span class="italic text-[#4d37c5]">{{ titleAccent }}</span>
      </h1>
      <p class="mt-6 max-w-md text-[1.32rem] leading-relaxed text-[#474c66]">
        {{ description }}
      </p>
    </div>

    <div class="mt-10 flex items-center gap-4">
      <div class="flex -space-x-2">
        <span class="h-9 w-9 rounded-full border-2 border-white bg-[#5d4ac8]"></span>
        <span class="h-9 w-9 rounded-full border-2 border-white bg-[#7f6be1]"></span>
        <span class="h-9 w-9 rounded-full border-2 border-white bg-[#a091ea]"></span>
      </div>
      <p class="text-sm text-[#3d415c]">
        {{ footerLead }} <span class="font-semibold text-[#17192f]">{{ footerHighlight }}</span>
        {{ footerTail }}
      </p>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AuthPromoPanel',
  props: {
    badgeText: {
      type: String,
      default: 'THE ACADEMIC CURATOR',
    },
    titleLine1: {
      type: String,
      required: true,
    },
    titleLine2: {
      type: String,
      required: true,
    },
    titleLine3: {
      type: String,
      required: true,
    },
    titleAccent: {
      type: String,
      default: 'narrative.',
    },
    description: {
      type: String,
      required: true,
    },
    footerLead: {
      type: String,
      required: true,
    },
    footerHighlight: {
      type: String,
      required: true,
    },
    footerTail: {
      type: String,
      required: true,
    },
  },
})
</script>

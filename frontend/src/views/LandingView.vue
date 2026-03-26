<template>
  <div class="min-h-screen bg-[#fcf8ff] text-[#1c1a24]">
    <LandingTopBar />

    <main class="pt-24 sm:pt-24">
      <LandingHeroSection />
      <LandingTrustedBySection />
      <LandingFeaturesSection />
      <LandingTestimonialsSection />
      <LandingFinalCta />
    </main>

    <LandingFooter />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import LandingTopBar from '@/components/landing/LandingTopBar.vue'
import LandingHeroSection from '@/components/landing/LandingHeroSection.vue'
import LandingTrustedBySection from '@/components/landing/LandingTrustedBySection.vue'
import LandingFeaturesSection from '@/components/landing/LandingFeaturesSection.vue'
import LandingTestimonialsSection from '@/components/landing/LandingTestimonialsSection.vue'
import LandingFinalCta from '@/components/landing/LandingFinalCta.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

export default defineComponent({
  name: 'LandingView',
  components: {
    LandingTopBar,
    LandingHeroSection,
    LandingTrustedBySection,
    LandingFeaturesSection,
    LandingTestimonialsSection,
    LandingFinalCta,
    LandingFooter,
  },
})
</script>

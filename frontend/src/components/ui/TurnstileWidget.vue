<template>
  <div class="space-y-1">
    <div ref="widgetContainer" class="min-h-[65px]" />
    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

const TURNSTILE_SCRIPT_ID = 'cloudflare-turnstile-script'
const TURNSTILE_SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'

interface TurnstileApi {
  render: (
    container: HTMLElement,
    options: {
      sitekey: string
      theme: 'light' | 'dark' | 'auto'
      callback: (token: string) => void
      'expired-callback': () => void
      'error-callback': () => void
    },
  ) => string
  remove: (widgetId: string) => void
  reset: (widgetId: string) => void
}

const getTurnstileApi = (): TurnstileApi | undefined => {
  const scope = window as Window & { turnstile?: TurnstileApi }
  return scope.turnstile
}

export default defineComponent({
  name: 'TurnstileWidget',
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    error: {
      type: String,
      default: '',
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      widgetId: null as string | null,
    }
  },
  computed: {
    siteKey(): string {
      return (import.meta.env.VITE_TURNSTILE_SITE_KEY as string) ?? ''
    },
  },
  async mounted() {
    if (!this.siteKey) {
      return
    }

    await this.ensureScriptLoaded()
    this.renderWidget()
  },
  beforeUnmount() {
    const turnstile = getTurnstileApi()
    if (turnstile && this.widgetId) {
      turnstile.remove(this.widgetId)
    }
  },
  methods: {
    ensureScriptLoaded(): Promise<void> {
      return new Promise((resolve) => {
        const existing = document.getElementById(TURNSTILE_SCRIPT_ID) as HTMLScriptElement | null

        if (existing && getTurnstileApi()) {
          resolve()
          return
        }

        if (existing) {
          existing.addEventListener('load', () => resolve(), { once: true })
          return
        }

        const script = document.createElement('script')
        script.id = TURNSTILE_SCRIPT_ID
        script.src = TURNSTILE_SCRIPT_SRC
        script.async = true
        script.defer = true
        script.onload = () => resolve()
        document.head.appendChild(script)
      })
    },
    renderWidget() {
      const turnstile = getTurnstileApi()
      const container = this.$refs.widgetContainer as HTMLElement | undefined

      if (!turnstile || !container) {
        return
      }

      this.widgetId = turnstile.render(container, {
        sitekey: this.siteKey,
        theme: 'light',
        callback: (token: string) => this.$emit('update:modelValue', token),
        'expired-callback': () => this.$emit('update:modelValue', ''),
        'error-callback': () => this.$emit('update:modelValue', ''),
      })
    },
    reset() {
      const turnstile = getTurnstileApi()
      if (turnstile && this.widgetId) {
        turnstile.reset(this.widgetId)
      }
      this.$emit('update:modelValue', '')
    },
  },
})
</script>

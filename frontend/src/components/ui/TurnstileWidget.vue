<template>
  <div class="space-y-1">
    <div ref="widgetContainer" class="min-h-16.25" />
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
      themeObserver: null as MutationObserver | null,
      activeTheme: 'light' as 'light' | 'dark',
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
    this.activeTheme = this.getCurrentTheme()
    this.renderWidget()
    this.watchThemeChanges()
  },
  beforeUnmount() {
    if (this.themeObserver) {
      this.themeObserver.disconnect()
      this.themeObserver = null
    }

    const turnstile = getTurnstileApi()
    if (turnstile && this.widgetId) {
      turnstile.remove(this.widgetId)
    }
  },
  methods: {
    getCurrentTheme(): 'light' | 'dark' {
      return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    },
    watchThemeChanges() {
      const root = document.documentElement
      this.themeObserver = new MutationObserver(() => {
        const nextTheme = this.getCurrentTheme()
        if (nextTheme === this.activeTheme) {
          return
        }

        this.activeTheme = nextTheme
        this.renderWidget()
      })

      this.themeObserver.observe(root, {
        attributes: true,
        attributeFilter: ['class'],
      })
    },
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

      if (this.widgetId) {
        turnstile.remove(this.widgetId)
        this.widgetId = null
      }

      container.innerHTML = ''
      this.$emit('update:modelValue', '')

      this.widgetId = turnstile.render(container, {
        sitekey: this.siteKey,
        theme: this.activeTheme,
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

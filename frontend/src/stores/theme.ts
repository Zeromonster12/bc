import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export type ThemeMode = 'light' | 'dark'

const STORAGE_KEY = 'app-theme'

export const useThemeStore = defineStore('theme', () => {
  const themeMode = ref<ThemeMode>('light')

  const resolvedTheme = computed<ThemeMode>(() => themeMode.value)

  function applyTheme(mode: ThemeMode): void {
    if (typeof document === 'undefined') return
    document.documentElement.classList.toggle('dark', mode === 'dark')
  }

  function setTheme(mode: ThemeMode, persist = true): void {
    themeMode.value = mode
    applyTheme(mode)
    if (persist && typeof window !== 'undefined') {
      window.localStorage.setItem(STORAGE_KEY, mode)
    }
  }

  function toggleTheme(): void {
    setTheme(themeMode.value === 'dark' ? 'light' : 'dark')
  }

  function initTheme(): void {
    if (typeof window === 'undefined') return
    const stored = window.localStorage.getItem(STORAGE_KEY)
    if (stored === 'dark' || stored === 'light') {
      setTheme(stored, false)
      return
    }

    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false
    setTheme(prefersDark ? 'dark' : 'light', false)
  }

  return {
    themeMode,
    resolvedTheme,
    initTheme,
    setTheme,
    toggleTheme,
    applyTheme,
  }
})

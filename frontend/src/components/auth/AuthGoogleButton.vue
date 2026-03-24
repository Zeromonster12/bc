<template>
  <button
    type="button"
    class="mb-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    @click="$emit('click')"
  >
    <svg viewBox="0 0 24 24" class="h-4 w-4" aria-hidden="true">
      <path
        fill="#EA4335"
        d="M12 10.2v3.9h5.5c-.2 1.2-.9 2.3-1.9 3.1l3 2.3c1.8-1.6 2.9-4 2.9-6.9 0-.7-.1-1.5-.2-2.2H12z"
      />
      <path
        fill="#34A853"
        d="M12 21c2.6 0 4.8-.9 6.4-2.5l-3-2.3c-.8.5-1.9.9-3.4.9-2.6 0-4.8-1.8-5.6-4.2l-3.1 2.4C4.9 18.6 8.1 21 12 21z"
      />
      <path
        fill="#4A90E2"
        d="M6.4 12.9c-.2-.5-.3-1.1-.3-1.7s.1-1.2.3-1.7L3.3 7.1C2.5 8.6 2 10.2 2 11.9s.5 3.3 1.3 4.8l3.1-2.4z"
      />
      <path
        fill="#FBBC05"
        d="M12 6.9c1.4 0 2.7.5 3.7 1.4l2.8-2.8C16.8 3.9 14.6 3 12 3 8.1 3 4.9 5.4 3.3 9l3.1 2.4c.8-2.7 3-4.5 5.6-4.5z"
      />
    </svg>
    Sign in with Google
  </button>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'AuthGoogleButton',
  emits: ['click'],
})
</script>

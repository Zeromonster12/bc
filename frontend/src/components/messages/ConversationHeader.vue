<template>
  <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
    <button
      class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 md:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
      @click="$emit('back')"
    >
      &larr;
    </button>
    <div
      class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
    >
      <img
        v-if="avatarUrl"
        :src="avatarUrl"
        alt=""
        class="h-full w-full rounded-full object-cover"
      />
      <span v-else>{{ avatarInitials }}</span>
    </div>
    <div class="min-w-0">
      <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ title || participantNames || 'Conversation' }}
      </p>
      <p v-if="subtitle" class="truncate text-xs text-slate-500 dark:text-slate-400">
        {{ subtitle }}
      </p>
      <p v-if="subtitleSecondary" class="truncate text-xs text-slate-500 dark:text-slate-400">
        {{ subtitleSecondary }}
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ConversationHeader',
  props: {
    title: {
      type: String,
      default: '',
    },
    subtitle: {
      type: String,
      default: '',
    },
    subtitleSecondary: {
      type: String,
      default: '',
    },
    avatarUrl: {
      type: String,
      default: '',
    },
    participantNames: {
      type: String,
      default: '',
    },
  },
  emits: ['back'],
  computed: {
    avatarInitials(): string {
      const source = this.participantNames || 'Chat'
      const words = source
        .split(' ')
        .map((w) => w.trim())
        .filter(Boolean)
      const initials = words
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
      return (initials || 'C').toUpperCase()
    },
  },
})
</script>

<template>
  <div class="mx-3 mt-3 flex items-center gap-3 rounded-2xl bg-[#e8e3f2] px-4 py-3 dark:bg-slate-800">
    <button
      class="rounded-full p-2 text-[#5b5676] hover:bg-[#d7d1ec] hover:text-[#3f34a6] md:hidden dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
      @click="$emit('back')"
    >
      &larr;
    </button>
    <div
      class="flex h-10 w-10 items-center justify-center rounded-full bg-[#d2cce8] text-xs font-semibold text-[#4d466b] dark:bg-slate-700 dark:text-slate-200"
    >
      <img
        v-if="avatarUrl"
        :src="avatarUrl"
        alt=""
        class="h-full w-full rounded-full object-cover"
      />
      <span v-else>{{ avatarInitials }}</span>
    </div>
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ title || participantNames || 'Conversation' }}
      </p>
      <p v-if="participantsLabel || subtitle" class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
        <span class="truncate">{{ participantsLabel || subtitle }}</span>
        <span
          v-if="participantsExtraCount > 0"
          class="group relative shrink-0"
        >
          <span class="inline-flex cursor-help items-center rounded-full bg-[#d7d1ec] px-1.5 py-0.5 text-[10px] font-semibold text-[#4d466b] dark:bg-slate-700 dark:text-slate-200">
            +{{ participantsExtraCount }}
          </span>
          <span
            v-if="participantsTooltip"
            class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-max max-w-[18rem] -translate-x-1/2 rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-medium leading-4 text-slate-700 opacity-0 shadow-lg transition-all duration-150 group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-slate-800 dark:text-slate-200"
          >
            <span
              class="absolute -top-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-white dark:bg-slate-800"
            />
            {{ participantsTooltip }}
          </span>
        </span>
      </p>
      <p v-if="subtitleSecondary" class="truncate text-xs text-slate-500 dark:text-slate-400">
        {{ subtitleSecondary }}
      </p>
    </div>
    <details v-if="showGroupActions" class="relative shrink-0">
      <summary
        class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-2xl bg-[#d7d1ec] text-[#474a61] transition hover:bg-[#ccc4e8] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
      >
        <span class="text-lg leading-none">...</span>
      </summary>
      <div class="absolute right-0 top-full z-30 mt-2 w-52 rounded-2xl bg-white p-1.5 dark:bg-slate-900">
        <button
          class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:text-slate-200 dark:hover:bg-slate-800"
          @click="$emit('rename-group')"
        >
          Rename group
        </button>
        <button
          class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:text-slate-200 dark:hover:bg-slate-800"
          @click="$emit('change-group-avatar')"
        >
          Change group photo
        </button>
        <button
          class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:text-slate-200 dark:hover:bg-slate-800"
          @click="$emit('add-group-users')"
        >
          Add users
        </button>
        <button
          class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
          @click="$emit('delete-group')"
        >
          Delete group
        </button>
      </div>
    </details>
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
    participantsLabel: {
      type: String,
      default: '',
    },
    participantsExtraCount: {
      type: Number,
      default: 0,
    },
    participantsTooltip: {
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
    showGroupActions: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['back', 'rename-group', 'change-group-avatar', 'add-group-users', 'delete-group'],
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

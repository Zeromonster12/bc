<template>
  <div
    v-if="visible"
    class="space-y-2 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80"
  >
    <BaseInput
      :model-value="recipientQuery"
      label="Recipient"
      placeholder="Search by name or email"
      @update:modelValue="$emit('update:recipientQuery', $event)"
    />
    <p
      v-if="selectedRecipient"
      class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
    >
      Selected: <strong>{{ selectedRecipient.name }}</strong>
      <span class="text-emerald-600 dark:text-emerald-400">({{ selectedRecipient.email }})</span>
      <button
        type="button"
        class="ml-2 text-emerald-700 underline underline-offset-2 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-200"
        @click="$emit('clearRecipient')"
      >
        Clear
      </button>
    </p>
    <div
      v-if="recipientQuery.trim().length >= 2"
      class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
    >
      <p v-if="searchingUsers" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">Searching users...</p>
      <p
        v-else-if="recipientOptions.length === 0"
        class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400"
      >
        No users found.
      </p>
      <button
        v-for="option in recipientOptions"
        :key="option.id"
        type="button"
        class="block w-full border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
        @click="$emit('selectRecipient', option)"
      >
        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.name }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ option.email }}</p>
      </button>
    </div>
    <BaseButton
      size="sm"
      variant="primary"
      :loading="creating"
      @click="$emit('submit')"
      class="w-full"
    >
      Start conversation
    </BaseButton>
    <BaseAlert v-if="errorMessage" type="error" class="text-xs" :message="errorMessage" />
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

interface RecipientOption {
  id: number
  name: string
  email: string
}

export default defineComponent({
  name: 'NewConversationForm',
  components: { BaseInput, BaseButton, BaseAlert },
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    recipientQuery: {
      type: String,
      default: '',
    },
    selectedRecipient: {
      type: Object as PropType<RecipientOption | null>,
      default: null,
    },
    recipientOptions: {
      type: Array as PropType<RecipientOption[]>,
      default: () => [],
    },
    searchingUsers: {
      type: Boolean,
      default: false,
    },
    errorMessage: {
      type: String,
      default: '',
    },
    creating: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update:recipientQuery', 'selectRecipient', 'clearRecipient', 'submit'],
})
</script>

<template>
  <div
    v-if="visible"
    class="space-y-2 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80"
  >
    <div class="grid grid-cols-2 rounded-full border border-[#ded8ee] bg-[#e8e3f2] p-1 dark:border-slate-700 dark:bg-slate-900">
      <button
        type="button"
        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
        :class="mode === 'direct' ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
        @click="$emit('update:mode', 'direct')"
      >
        Direct
      </button>
      <button
        type="button"
        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
        :class="mode === 'group' ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
        @click="$emit('update:mode', 'group')"
      >
        Group
      </button>
    </div>

    <div v-if="mode === 'group'" class="space-y-2">
      <BaseInput
        :model-value="groupSubject"
        label="Group name"
        placeholder="Project Team Chat"
        @update:modelValue="$emit('update:groupSubject', $event)"
      />

      <div>
        <label class="mb-2 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">Project</label>
        <select
          :value="selectedProjectId ?? ''"
          class="block w-full rounded-lg border-0 bg-[#f1edf8] px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100 dark:focus:ring-indigo-400"
          @change="$emit('update:selectedProjectId', Number(($event.target as HTMLSelectElement).value) || null)"
        >
          <option value="">Select project</option>
          <option v-for="project in projectOptions" :key="project.id" :value="project.id">
            {{ project.title }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="mode === 'direct'" class="space-y-1">
      <label class="mb-1 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">Recipient</label>
      <div class="relative">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-lg border-0 bg-[#f1edf8] px-3 py-2 text-left text-sm text-slate-900 outline-none transition hover:bg-[#ece6f7] focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-indigo-400"
          @click="directDropdownOpen = !directDropdownOpen"
        >
          <span class="truncate">{{ directDropdownLabel }}</span>
          <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">v</span>
        </button>

        <div
          v-if="directDropdownOpen"
          class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
        >
          <div class="sticky top-0 z-10 border-b border-slate-100 bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
            <input
              :value="recipientQuery"
              type="text"
              placeholder="Search by name or email"
              class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:bg-white dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900"
              @input="$emit('update:recipientQuery', ($event.target as HTMLInputElement).value)"
            />
          </div>

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
            @click="onSelectDirectOption(option)"
          >
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ option.email }}</p>
          </button>
        </div>
      </div>
    </div>

    <div v-if="mode === 'group'" class="space-y-2">
      <label class="mb-1 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">Participants</label>
      <div class="relative">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-lg border-0 bg-[#f1edf8] px-3 py-2 text-left text-sm text-slate-900 outline-none transition hover:bg-[#ece6f7] focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-indigo-400"
          @click="groupDropdownOpen = !groupDropdownOpen"
        >
          <span class="truncate">{{ groupDropdownLabel }}</span>
          <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">v</span>
        </button>

        <div
          v-if="groupDropdownOpen"
          class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
        >
          <div class="sticky top-0 z-10 border-b border-slate-100 bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
            <input
              v-model="groupSearchQuery"
              type="text"
              placeholder="Search project member"
              class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:bg-white dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900"
            />
          </div>

          <p v-if="searchingUsers" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">Loading project members...</p>
          <p v-else-if="groupFilteredRecipientOptions.length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
            No users found.
          </p>
          <button
            v-for="option in groupFilteredRecipientOptions"
            :key="option.id"
            type="button"
            class="block w-full border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
            @click="onSelectGroupOption(option)"
          >
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ option.email }}</p>
          </button>
        </div>
      </div>
    </div>

    <p
      v-if="mode === 'direct' && selectedRecipient"
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
      v-if="mode === 'group' && selectedParticipants.length"
      class="flex flex-wrap gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2 py-2 dark:border-indigo-500/30 dark:bg-indigo-500/10"
    >
      <span
        v-for="participant in selectedParticipants"
        :key="participant.id"
        class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-1 text-xs font-medium text-indigo-700 dark:bg-slate-800 dark:text-indigo-300"
      >
        {{ participant.name }}
        <button
          type="button"
          class="text-indigo-500 transition hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-100"
          @click="$emit('removeParticipant', participant.id)"
        >
          x
        </button>
      </span>
    </div>

    <BaseButton
      size="sm"
      variant="primary"
      :loading="creating"
      @click="$emit('submit')"
      class="w-full"
    >
      {{ mode === 'group' ? 'Create group chat' : 'Start conversation' }}
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

interface ProjectOption {
  id: number
  title: string
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
    mode: {
      type: String as PropType<'direct' | 'group'>,
      default: 'direct',
    },
    groupSubject: {
      type: String,
      default: '',
    },
    selectedProjectId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    directDropdownValue: {
      type: Number as PropType<number | null>,
      default: null,
    },
    participantDropdownValue: {
      type: Number as PropType<number | null>,
      default: null,
    },
    projectOptions: {
      type: Array as PropType<ProjectOption[]>,
      default: () => [],
    },
    selectedRecipient: {
      type: Object as PropType<RecipientOption | null>,
      default: null,
    },
    selectedParticipants: {
      type: Array as PropType<RecipientOption[]>,
      default: () => [],
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
  emits: [
    'update:recipientQuery',
    'update:mode',
    'update:groupSubject',
    'update:selectedProjectId',
    'update:directDropdownValue',
    'update:participantDropdownValue',
    'addParticipantFromDropdown',
    'selectRecipient',
    'removeParticipant',
    'clearRecipient',
    'submit',
  ],
  data() {
    return {
      directDropdownOpen: false,
      groupDropdownOpen: false,
      groupSearchQuery: '',
    }
  },
  computed: {
    directDropdownLabel(): string {
      if (this.selectedRecipient?.id) {
        return `${this.selectedRecipient.name} (${this.selectedRecipient.email})`
      }

      const selectedId = Number(this.directDropdownValue ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        return 'Select recipient'
      }

      const selected = this.recipientOptions.find((option) => option.id === selectedId)
      if (!selected) {
        return 'Select recipient'
      }

      return `${selected.name} (${selected.email})`
    },
    groupDropdownLabel(): string {
      const selectedId = Number(this.participantDropdownValue ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        return 'Add participant'
      }

      const selected = this.recipientOptions.find((option) => option.id === selectedId)
      if (!selected) {
        return 'Add participant'
      }

      return `${selected.name} (${selected.email})`
    },
    groupFilteredRecipientOptions(): RecipientOption[] {
      const q = this.groupSearchQuery.trim().toLowerCase()
      if (!q) {
        return this.recipientOptions
      }

      return this.recipientOptions.filter((option) => {
        const name = String(option.name ?? '').toLowerCase()
        const email = String(option.email ?? '').toLowerCase()
        return name.includes(q) || email.includes(q)
      })
    },
  },
  methods: {
    onSelectDirectOption(option: RecipientOption) {
      this.$emit('update:directDropdownValue', option.id)
      this.$emit('selectRecipient', option)
      this.directDropdownOpen = false
    },
    onSelectGroupOption(option: RecipientOption) {
      this.$emit('update:participantDropdownValue', option.id)
      this.$emit('addParticipantFromDropdown', option.id)
      this.groupSearchQuery = ''
      this.groupDropdownOpen = false
    },
  },
})
</script>
